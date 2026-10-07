<?php

namespace App\Http\Controllers;

use App\Exceptions\CostumeItemUnavailableException;
use App\Mail\ItemTakenOverMail;
use App\Models\CostumeItem;
use App\Models\User;
use App\Notifications\ItemTakenOverNotification;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use function Illuminate\Support\defer;

class ScanController extends Controller
{
    // parāda noskenēto tērpa vienību un nākamo soli atkarībā no stāvokļa
    public function show($code)
    {
        $item = CostumeItem::with(['costume.group', 'user'])
            ->where('qr_code', $code)
            ->first();

        // QR kods neatbilst nevienai vienībai – visticamāk vecā birka, kas aizstāta ar jaunu
        if (! $item) {
            return response()->view('scan.invalid', [], 404);
        }

        if ($item->assigned_to) {
            return $this->assignedView($item);
        }

        if (! Auth::check()) {
            return view('scan.authenticate', compact('item'));
        }

        // pieslēdzies, bet nav šīs grupas dalībnieks
        if (! Auth::user()->inGroup($item->costume->group)) {
            return view('scan.denied', compact('item'));
        }

        return view('scan.confirm', compact('item'));
    }

    // pārbauda lietotāja paroli un pieslēdz viņu, lai zinātu, kas skenē
    public function authenticate(Request $request, $code)
    {
        $item = CostumeItem::with('costume.group')->where('qr_code', $code)->firstOrFail();

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // aizsardzība pret paroļu uzlaušanu ar mēģinājumu ierobežošanu
        $this->ensureIsNotRateLimited($request, $code);

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($this->throttleKey($request, $code));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // novecojusi pagaidu parole vairs neder – tāpat kā parastajā pieslēgšanās lapā
        if (Auth::user()->temporaryPasswordExpired()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => User::TEMPORARY_PASSWORD_EXPIRED_MESSAGE,
            ]);
        }

        // tikai šīs grupas dalībnieks drīkst turpināt
        if (! Auth::user()->inGroup($item->costume->group)) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey($request, $code));

            throw ValidationException::withMessages([
                'email' => 'You are not a member of this costume\'s group.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, $code));

        $request->session()->regenerate();

        return redirect("/scan/{$code}");
    }

    // pārtrauc pieprasījumu, ja no šī e-pasta / ierīces jau bijis par daudz neveiksmīgu mēģinājumu
    protected function ensureIsNotRateLimited(Request $request, string $code): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $code), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $code));

        // ļauj skata slānim uz šo laiku atspējot formu
        $request->session()->flash('scanLockSeconds', $seconds);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    // atslēga mēģinājumu skaitīšanai: e-pasts + ierīces IP + konkrētais QR kods
    protected function throttleKey(Request $request, string $code): string
    {
        return Str::transliterate(
            Str::lower((string) $request->input('email')).'|'.$request->ip().'|'.$code
        );
    }

    // piešķir tērpa vienību pašam pieslēgtajam lietotājam
    public function assign(Request $request, $code)
    {
        $item = CostumeItem::with('costume.group')->where('qr_code', $code)->firstOrFail();

        if (! Auth::check()) {
            return redirect("/scan/{$code}");
        }

        if ($item->assigned_to) {
            return $this->assignedView($item);
        }

        $this->authorize('claim', $item);

        try {
            $item->assignTo(Auth::user(), Auth::user());
        } catch (CostumeItemUnavailableException) {
            // kāds cits paspēja pirmais (piem. divkāršs klikšķis vai vēl kāds skenēja to pašu kodu tajā pašā mirklī) –
            // atgriežamies uz show(), kas pēc patiesā stāvokļa pats izlems, ko rādīt
            return redirect("/scan/{$code}");
        }

        return view('scan.success', compact('item'));
    }

    // pārņem citam dalībniekam izsniegtu vienību sev
    public function takeover(Request $request, $code)
    {
        $item = CostumeItem::with('costume.group')->where('qr_code', $code)->firstOrFail();

        if (! Auth::check()) {
            return redirect("/scan/{$code}");
        }

        // ja vienība pa to laiku jau atbrīvota – aizved uz parasto piešķiršanas plūsmu
        if (! $item->assigned_to) {
            return redirect("/scan/{$code}");
        }

        $this->authorize('takeOver', $item);

        try {
            $previousHolder = $item->transferTo(Auth::user());
        } catch (CostumeItemUnavailableException) {
            // stāvoklis mainījies starplaikā (piem. turētājs pats to atdeva) – show() izlems, ko rādīt tālāk
            return redirect("/scan/{$code}");
        }

        // pārņemšana nenotiek klusi: skolotājs redz to panelī, iepriekšējais turētājs saņem e-pastu
        // (ja tā nebija norunāta nodošana, skolotājs vienību var atdot atpakaļ)
        if ($previousHolder) {
            $taker = Auth::user();

            $item->costume->group?->admin?->notify(new ItemTakenOverNotification($item, $previousHolder, $taker));

            defer(fn () => rescue(fn () => Mail::to($previousHolder->email)->send(new ItemTakenOverMail($previousHolder, $item, $taker))));
        }

        return view('scan.success', compact('item'));
    }

    // QR koda PNG lejupielāde – faila nosaukumā izmanto salasāmo kodu (piem. qr-BRU-01.png)
    public function downloadQr($code)
    {
        $label = CostumeItem::where('qr_code', $code)->value('code') ?? $code;

        $png = QrCode::format('png')->size(300)->generate(url('/scan/'.$code));

        return response($png)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="qr-'.$label.'.png"');
    }

    // parāda jau izsniegtas vienības lapu kopā ar norādi, vai skenētājs to var pārņemt sev
    protected function assignedView(CostumeItem $item)
    {
        $item->loadMissing(['costume.group', 'user']);

        $isHolder = Auth::id() === $item->assigned_to;

        $canTakeOver = Auth::check()
            && ! $isHolder
            && Auth::user()->inGroup($item->costume->group);

        // QR birka ir publiska – turētāja vārdu redz tikai pats turētājs, grupas dalībnieki un grupas skolotājs
        $canSeeHolder = $isHolder || $canTakeOver
            || (Auth::check() && $item->costume->group && Auth::user()->ownsGroup($item->costume->group));

        return view('scan.assigned', compact('item', 'isHolder', 'canTakeOver', 'canSeeHolder'));
    }
}

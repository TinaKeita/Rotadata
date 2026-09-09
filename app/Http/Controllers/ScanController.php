<?php

namespace App\Http\Controllers;

use App\Models\CostumeItem;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        $item->assignTo(Auth::user(), Auth::user());

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

        $item->transferTo(Auth::user());

        return view('scan.success', compact('item'));
    }

    // parāda jau izsniegtas vienības lapu kopā ar norādi, vai skenētājs to var pārņemt sev
    protected function assignedView(CostumeItem $item)
    {
        $item->loadMissing(['costume.group', 'user']);

        $isHolder = Auth::id() === $item->assigned_to;

        $canTakeOver = Auth::check()
            && ! $isHolder
            && Auth::user()->inGroup($item->costume->group);

        return view('scan.assigned', compact('item', 'isHolder', 'canTakeOver'));
    }
}

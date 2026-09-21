// Ritināšanas atklāšana landing lapai: elementi ar data-reveal parādās, kad nonāk skatā.
// Slēptais stāvoklis tiek uzlikts TIKAI no JS – ja skripts nenostrādā, lapa paliek pilnībā lasāma.
// Atklātības stāvoklis tiek glabāts DOM atribūtā (data-reveal="in"), nevis JS īpašībā.

const TRANSITION = 'opacity 700ms cubic-bezier(.22,.61,.36,1), transform 700ms cubic-bezier(.22,.61,.36,1)';
const FAILSAFE_MS = 900;

let observerAlive = false;

function motionOff() {
    return Boolean(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
}

function setVisible(el) {
    el.style.opacity = '1';
    el.style.transform = 'none';
}

function revealAll() {
    document.querySelectorAll('[data-reveal]').forEach((el) => {
        el.setAttribute('data-reveal', 'in');
        setVisible(el);
    });
}

function show(el) {
    if (el.getAttribute('data-reveal') === 'in') {
        return;
    }

    el.setAttribute('data-reveal', 'in');
    const delay = parseInt(el.getAttribute('data-reveal-delay') || '0', 10);
    setTimeout(() => setVisible(el), delay);
}

// rezerves ceļš vidēm, kur IntersectionObserver nenostrādā: atklāj visu, kas ir skata apakšējās malas līmenī vai augstāk
function sweep() {
    const limit = (window.innerHeight || 800) * 0.94;

    document.querySelectorAll('[data-reveal=""]').forEach((el) => {
        if (el.getBoundingClientRect().top < limit) {
            show(el);
        }
    });
}

function arm() {
    const targets = document.querySelectorAll('[data-reveal=""]');
    if (!targets.length) {
        return;
    }

    let observer = null;
    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver((entries) => {
            observerAlive = true;

            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                show(entry.target);
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 });
    }

    targets.forEach((el) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(16px)';
    });

    // izkārtojuma nolasīšana piefiksē slēpto stāvokli pirms pārejas ieslēgšanas –
    // citādi jau uzzīmēts elements vispirms 700 ms izgaistu un tikai tad parādītos
    void document.body.offsetHeight;

    targets.forEach((el) => {
        el.style.transition = TRANSITION;
        if (observer) {
            observer.observe(el);
        }
    });

    sweep();
    requestAnimationFrame(sweep);
    window.addEventListener('scroll', sweep, { passive: true });
    window.addEventListener('resize', sweep);

    // drošības tīkls: ja novērotājs 900 ms laikā nav nostrādājis ne reizi (nav atbalsta vai vide neizsauc notikumus),
    // atklājam visu. Ja novērotājs darbojas, visu uzreiz NEatklājam – citādi ritināšanas efekts pazustu visai lapas lejasdaļai
    setTimeout(() => {
        if (!observerAlive) {
            revealAll();
        }
    }, FAILSAFE_MS);
}

function init() {
    if (motionOff()) {
        revealAll();
        return;
    }

    try {
        arm();
    } catch (error) {
        revealAll();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

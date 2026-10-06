<svg class="auth-frame" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="auth-frame-gradient" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="var(--auth-frame-color)" stop-opacity="0" />
            <stop offset=".48" stop-color="var(--auth-frame-color)" stop-opacity=".78" />
            <stop offset="1" stop-color="var(--auth-frame-color)" stop-opacity="0" />
        </linearGradient>
    </defs>
    <g class="auth-frame-lines">
        <path class="frame-primary" d="M2 0V13L7 18H13M2 39V58M2 87V100" />
        <path class="frame-primary" d="M98 0V13L93 18H87M98 40V59M98 87V100" />
        <path class="frame-primary" d="M0 3H10L14 7V12M100 3H90L86 7V12" />
        <path class="frame-primary" d="M0 97H10L14 93V88M100 97H90L86 93V88" />
        <path class="frame-secondary" d="M5 25H12M5 74H11M95 27H88M95 72H89" />
        <path class="frame-secondary" d="M19 5H27M81 5H73M19 95H27M81 95H73" />
        <path class="frame-flow" d="M2 39V58" />
        <path class="frame-flow" d="M98 40V59" />
    </g>
    <g class="auth-frame-points">
        <circle cx="13" cy="18" r=".42" />
        <circle cx="87" cy="18" r=".42" />
        <circle cx="14" cy="7" r=".32" />
        <circle cx="86" cy="7" r=".32" />
        <circle cx="14" cy="93" r=".32" />
        <circle cx="86" cy="93" r=".32" />
    </g>
</svg>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const page = document.querySelector('.login-page');
        const frame = page?.querySelector('.auth-frame');
        if (!page || !frame) return;

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const paths = frame.querySelectorAll('.frame-primary, .frame-secondary');
        const flows = frame.querySelectorAll('.frame-flow');
        if (reducedMotion) {
            frame.classList.add('is-visible');
            return;
        }

        page.classList.add('auth-frame-ready');
        if (window.gsap) {
            paths.forEach((path) => {
                const length = path.getTotalLength();
                window.gsap.set(path, { strokeDasharray: length, strokeDashoffset: length });
            });
            frame.classList.add('is-visible');
            const timeline = window.gsap.timeline({ defaults: { ease: 'power2.out' } });
            timeline.to(paths, {
                strokeDashoffset: 0,
                opacity: 1,
                duration: .72,
                stagger: { each: .055, from: 'start' },
            });
        } else {
            frame.classList.add('is-visible');
        }

        page.addEventListener('focusin', (event) => {
            if (event.target.matches('input, select, textarea')) page.classList.add('auth-frame-engaged');
        });
        page.addEventListener('focusout', (event) => {
            if (event.target.matches('input, select, textarea') && !page.contains(document.activeElement)) {
                page.classList.remove('auth-frame-engaged');
            }
        });
        page.querySelectorAll('.btn-primary').forEach((button) => {
            button.addEventListener('pointerenter', () => page.classList.add('auth-frame-engaged'));
            button.addEventListener('pointerleave', () => page.classList.remove('auth-frame-engaged'));
            button.addEventListener('focus', () => page.classList.add('auth-frame-engaged'));
            button.addEventListener('blur', () => page.classList.remove('auth-frame-engaged'));
        });
    });
</script>

import anime from 'animejs/lib/anime.es.js';

// ═══════════════════════════════════════════════════════════
// INITIALISATION GLOBALE
// ═══════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

    // ─────────────────────────────────────────────
    // 1. ANIMATION HERO — Apparition en cascade
    // ─────────────────────────────────────────────

    anime.timeline({ easing: 'easeOutExpo' })
        .add({
            targets: '.hero-badge',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
        })
        .add({
            targets: '.hero-title .word',
            translateY: [80, 0],
            opacity: [0, 1],
            duration: 1000,
            delay: anime.stagger(100),
        }, '-=400')
        .add({
            targets: '.hero-subtitle',
            translateY: [30, 0],
            opacity: [0, 1],
            duration: 800,
        }, '-=600')
        .add({
            targets: '.hero-cta',
            translateY: [30, 0],
            opacity: [0, 1],
            scale: [0.9, 1],
            duration: 800,
            delay: anime.stagger(100),
        }, '-=400')
        .add({
            targets: '.hero-trust',
            translateY: [20, 0],
            opacity: [0, 1],
            duration: 600,
        }, '-=400')
        .add({
            targets: '.hero-mockup',
            translateY: [50, 0],
            opacity: [0, 1],
            scale: [0.9, 1],
            duration: 1200,
        }, '-=1000');

    // ─────────────────────────────────────────────
    // 2. PARTICULES FLOTTANTES (Hero)
    // ─────────────────────────────────────────────

    const particles = document.querySelectorAll('.particle');
    particles.forEach(particle => {
        anime({
            targets: particle,
            translateY: [
                { value: -30, duration: 2000 },
                { value: 0,   duration: 2000 },
            ],
            translateX: [
                { value: anime.random(-20, 20), duration: 2000 },
                { value: 0,                     duration: 2000 },
            ],
            opacity: [
                { value: 0.8, duration: 2000 },
                { value: 0.3, duration: 2000 },
            ],
            loop: true,
            easing: 'easeInOutSine',
            delay: anime.random(0, 2000),
        });
    });

    // ─────────────────────────────────────────────
    // 3. GRILLE DE POINTS ANIMÉE (Background hero)
    // ─────────────────────────────────────────────

    const dots = document.querySelectorAll('.bg-dot');
    anime({
        targets: dots,
        scale: [
            { value: 1.5, duration: 1500 },
            { value: 1,   duration: 1500 },
        ],
        opacity: [
            { value: 0.6, duration: 1500 },
            { value: 0.2, duration: 1500 },
        ],
        delay: anime.stagger(50, { grid: [10, 10], from: 'center' }),
        loop: true,
        easing: 'easeInOutQuad',
    });

    // ─────────────────────────────────────────────
    // 4. COMPTEURS ANIMÉS (Stats)
    // ─────────────────────────────────────────────

    const counters = document.querySelectorAll('.counter');

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el     = entry.target;
                const target = parseInt(el.dataset.target);

                anime({
                    targets: { value: 0 },
                    value:   target,
                    duration: 2000,
                    easing:   'easeOutExpo',
                    round:    1,
                    update: (anim) => {
                        el.textContent = Math.round(anim.animations[0].currentValue)
                            .toLocaleString('fr-FR');
                    }
                });

                counterObserver.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => counterObserver.observe(counter));

    // ─────────────────────────────────────────────
    // 5. SCROLL REVEAL — Animations au scroll
    // ─────────────────────────────────────────────

    const scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                anime({
                    targets: entry.target,
                    translateY: [60, 0],
                    opacity: [0, 1],
                    duration: 1000,
                    easing: 'easeOutExpo',
                });
                scrollObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.reveal').forEach(el => {
        el.style.opacity = '0';
        scrollObserver.observe(el);
    });

    // ─────────────────────────────────────────────
    // 6. STAGGER — Cartes en cascade
    // ─────────────────────────────────────────────

    const staggerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const cards = entry.target.querySelectorAll('.stagger-item');
                anime({
                    targets: cards,
                    translateY: [60, 0],
                    opacity: [0, 1],
                    scale: [0.9, 1],
                    duration: 800,
                    delay: anime.stagger(100),
                    easing: 'easeOutExpo',
                });
                staggerObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.stagger-container').forEach(container => {
        container.querySelectorAll('.stagger-item').forEach(item => {
            item.style.opacity = '0';
        });
        staggerObserver.observe(container);
    });

    // ─────────────────────────────────────────────
    // 7. HOVER 3D — Cartes fonctionnalités
    // ─────────────────────────────────────────────

    document.querySelectorAll('.card-3d').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect    = card.getBoundingClientRect();
            const x       = e.clientX - rect.left;
            const y       = e.clientY - rect.top;
            const centerX = rect.width  / 2;
            const centerY = rect.height / 2;
            const rotateX = (y - centerY) / 15;
            const rotateY = (centerX - x) / 15;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
        });
    });

    // ─────────────────────────────────────────────
    // 8. BARRES ANIMÉES DU MOCKUP
    // ─────────────────────────────────────────────

    const animateBars = () => {
        anime({
            targets: '.mock-bar',
            scaleY: [0, 1],
            duration: 1200,
            delay: anime.stagger(100, { start: 800 }),
            easing: 'easeOutExpo',
        });
    };
    animateBars();

    // ─────────────────────────────────────────────
    // 9. PULSE EFFECT — Bouton CTA principal
    // ─────────────────────────────────────────────

    anime({
        targets: '.pulse-ring',
        scale: [1, 1.5],
        opacity: [0.5, 0],
        easing: 'easeOutSine',
        duration: 2000,
        loop: true,
    });

    // ─────────────────────────────────────────────
    // 10. MORPHING SVG — Forme décorative
    // ─────────────────────────────────────────────

    anime({
        targets: '.morph-shape path',
        d: [
            { value: 'M60,-40C70,-20,60,20,40,40C20,60,-20,60,-40,40C-60,20,-60,-20,-40,-40C-20,-60,20,-60,40,-40C60,-20,70,-40,60,-40Z' },
            { value: 'M50,-50C70,-30,60,10,40,30C20,50,-10,70,-40,50C-70,30,-60,-10,-40,-30C-20,-50,20,-70,50,-50Z' },
        ],
        easing: 'easeInOutQuad',
        duration: 4000,
        loop: true,
        direction: 'alternate',
    });

    // ─────────────────────────────────────────────
    // 11. SCROLL PROGRESS BAR
    // ─────────────────────────────────────────────

    window.addEventListener('scroll', () => {
        const scrollTop    = document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const progress     = (scrollTop / scrollHeight) * 100;

        const progressBar = document.querySelector('.scroll-progress');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }
    });

    // ─────────────────────────────────────────────
    // 12. NAVIGATION SMOOTH SCROLL
    // ─────────────────────────────────────────────

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ─────────────────────────────────────────────
    // 13. FAQ ACCORDÉON ANIMÉ
    // ─────────────────────────────────────────────

    document.querySelectorAll('.faq-item').forEach(item => {
        const summary = item.querySelector('summary');
        const content = item.querySelector('.faq-content');

        summary.addEventListener('click', (e) => {
            e.preventDefault();
            const isOpen = item.hasAttribute('open');

            if (isOpen) {
                anime({
                    targets: content,
                    height: [content.scrollHeight, 0],
                    opacity: [1, 0],
                    duration: 300,
                    easing: 'easeInQuad',
                    complete: () => item.removeAttribute('open'),
                });
            } else {
                item.setAttribute('open', '');
                content.style.height = '0';
                content.style.opacity = '0';
                anime({
                    targets: content,
                    height: [0, content.scrollHeight],
                    opacity: [0, 1],
                    duration: 400,
                    easing: 'easeOutQuad',
                    complete: () => content.style.height = 'auto',
                });
            }
        });
    });

});
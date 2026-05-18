import anime from 'animejs/lib/anime.es.js';

// ═══════════════════════════════════════════════════════════
// INITIALISATION GLOBALE
// ═══════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

    initCustomCursor();
    initMeshGradient();
    initParticleNetwork();
    initStarryBackground();
    initSplitTextHero();
    initHeroAnimations();
    initMagneticButtons();
    initTilt3D();
    initSpotlight();
    initCounters();
    initScrollReveal();
    initStaggerCards();
    initParallax();
    initMockupAnimations();
    initFAQ();
    initScrollProgress();
    initSmoothScroll();
    initMarquee();
});

// ═══════════════════════════════════════════════════════════
// 1. CURSEUR MAGNÉTIQUE CUSTOM
// ═══════════════════════════════════════════════════════════

function initCustomCursor() {
    if (window.matchMedia('(pointer: coarse)').matches) return;

    const cursor    = document.createElement('div');
    const cursorDot = document.createElement('div');

    cursor.className    = 'custom-cursor';
    cursorDot.className = 'custom-cursor-dot';

    document.body.appendChild(cursor);
    document.body.appendChild(cursorDot);

    let mouseX = 0, mouseY = 0;
    let cursorX = 0, cursorY = 0;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
        cursorDot.style.transform = `translate(${mouseX}px, ${mouseY}px)`;
    });

    const animateCursor = () => {
        cursorX += (mouseX - cursorX) * 0.15;
        cursorY += (mouseY - cursorY) * 0.15;
        cursor.style.transform = `translate(${cursorX}px, ${cursorY}px)`;
        requestAnimationFrame(animateCursor);
    };
    animateCursor();

    // Hover effects sur éléments interactifs
    document.querySelectorAll('a, button, .card-tilt, summary').forEach(el => {
        el.addEventListener('mouseenter', () => cursor.classList.add('cursor-hover'));
        el.addEventListener('mouseleave', () => cursor.classList.remove('cursor-hover'));
    });
}

// ═══════════════════════════════════════════════════════════
// 2. MESH GRADIENT ANIMÉ (Canvas)
// ═══════════════════════════════════════════════════════════

function initMeshGradient() {
    const canvas = document.getElementById('mesh-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width, height;

    const resize = () => {
        width  = canvas.width  = window.innerWidth;
        height = canvas.height = canvas.parentElement.offsetHeight;
    };
    resize();
    window.addEventListener('resize', resize);

    const blobs = [
        { x: 0.2, y: 0.3, r: 200, color: 'rgba(59, 130, 246, 0.5)',  vx: 0.0008, vy: 0.0012 },
        { x: 0.8, y: 0.7, r: 250, color: 'rgba(6, 182, 212, 0.5)',   vx: -0.001,  vy: -0.0008 },
        { x: 0.5, y: 0.5, r: 220, color: 'rgba(16, 185, 129, 0.4)',  vx: 0.0012,  vy: -0.001 },
        { x: 0.3, y: 0.8, r: 180, color: 'rgba(139, 92, 246, 0.4)',  vx: -0.0009, vy: 0.0011 },
    ];

    const animate = () => {
        ctx.clearRect(0, 0, width, height);
        ctx.globalCompositeOperation = 'screen';

        blobs.forEach(blob => {
            blob.x += blob.vx;
            blob.y += blob.vy;

            if (blob.x < 0 || blob.x > 1) blob.vx *= -1;
            if (blob.y < 0 || blob.y > 1) blob.vy *= -1;

            const gradient = ctx.createRadialGradient(
                blob.x * width, blob.y * height, 0,
                blob.x * width, blob.y * height, blob.r
            );
            gradient.addColorStop(0,   blob.color);
            gradient.addColorStop(1,   'transparent');

            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, width, height);
        });

        requestAnimationFrame(animate);
    };
    animate();
}

// ═══════════════════════════════════════════════════════════
// 3. PARTICLE NETWORK (Canvas connecté)
// ═══════════════════════════════════════════════════════════

function initParticleNetwork() {
    const canvas = document.getElementById('particle-network');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width, height;

    const resize = () => {
        width  = canvas.width  = window.innerWidth;
        height = canvas.height = canvas.parentElement.offsetHeight;
    };
    resize();
    window.addEventListener('resize', resize);

    const particleCount = window.innerWidth < 768 ? 30 : 60;
    const particles = [];

    for (let i = 0; i < particleCount; i++) {
        particles.push({
            x:  Math.random() * width,
            y:  Math.random() * height,
            vx: (Math.random() - 0.5) * 0.5,
            vy: (Math.random() - 0.5) * 0.5,
            r:  Math.random() * 2 + 1,
        });
    }

    let mouseX = -1000, mouseY = -1000;
    canvas.parentElement.addEventListener('mousemove', (e) => {
        const rect = canvas.getBoundingClientRect();
        mouseX = e.clientX - rect.left;
        mouseY = e.clientY - rect.top;
    });

    const animate = () => {
        ctx.clearRect(0, 0, width, height);

        // Update particles
        particles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;

            if (p.x < 0 || p.x > width)  p.vx *= -1;
            if (p.y < 0 || p.y > height) p.vy *= -1;

            // Mouse interaction
            const dx = mouseX - p.x;
            const dy = mouseY - p.y;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist < 100) {
                const force = (100 - dist) / 100;
                p.x -= (dx / dist) * force * 2;
                p.y -= (dy / dist) * force * 2;
            }

            // Draw particle
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
            ctx.fill();
        });

        // Draw connections
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < 120) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(255, 255, 255, ${0.2 * (1 - dist / 120)})`;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                }
            }
        }

        requestAnimationFrame(animate);
    };
    animate();
}

// ═══════════════════════════════════════════════════════════
// 4. ÉTOILES SCINTILLANTES (Hero background)
// ═══════════════════════════════════════════════════════════

function initStarryBackground() {
    const container = document.querySelector('.starry-bg');
    if (!container) return;

    const starsCount = 50;
    for (let i = 0; i < starsCount; i++) {
        const star = document.createElement('div');
        star.className = 'star';
        star.style.top  = `${Math.random() * 100}%`;
        star.style.left = `${Math.random() * 100}%`;
        star.style.animationDelay = `${Math.random() * 3}s`;
        star.style.animationDuration = `${2 + Math.random() * 3}s`;
        container.appendChild(star);
    }
}

// ═══════════════════════════════════════════════════════════
// 5. SPLIT TEXT — Reveal caractère par caractère
// ═══════════════════════════════════════════════════════════

function initSplitTextHero() {
    document.querySelectorAll('.split-text').forEach(el => {
        const text = el.textContent;
        el.innerHTML = '';

        text.split('').forEach((char, i) => {
            const span = document.createElement('span');
            span.textContent = char === ' ' ? '\u00A0' : char;
            span.className = 'inline-block split-char';
            span.style.display = 'inline-block';
            el.appendChild(span);
        });
    });
}

// ═══════════════════════════════════════════════════════════
// 6. HERO ANIMATIONS — Timeline complexe
// ═══════════════════════════════════════════════════════════

function initHeroAnimations() {

    anime.timeline({ easing: 'easeOutExpo' })

        // Badge
        .add({
            targets: '.hero-badge',
            translateY: [40, 0],
            opacity:    [0, 1],
            scale:      [0.8, 1],
            duration:   1000,
        })

        // Titre — Split characters
        .add({
            targets: '.split-char',
            translateY: [100, 0],
            opacity:    [0, 1],
            rotateX:    [-90, 0],
            duration:   1200,
            delay:      anime.stagger(30),
        }, '-=600')

        // Subtitle reveal
        .add({
            targets: '.hero-subtitle',
            translateY: [40, 0],
            opacity:    [0, 1],
            duration:   1000,
        }, '-=800')

        // CTA buttons
        .add({
            targets: '.hero-cta',
            translateY: [40, 0],
            opacity:    [0, 1],
            scale:      [0.8, 1],
            duration:   1000,
            delay:      anime.stagger(150),
        }, '-=600')

        // Trust badges
        .add({
            targets: '.hero-trust > *',
            translateY: [20, 0],
            opacity:    [0, 1],
            duration:   600,
            delay:      anime.stagger(100),
        }, '-=400')

        // Mockup — Rotation 3D dramatique
        .add({
            targets: '.hero-mockup',
            translateY: [100, 0],
            opacity:    [0, 1],
            rotateY:    [25, 0],
            rotateX:    [10, 0],
            scale:      [0.8, 1],
            duration:   1500,
        }, '-=1400');
}

// ═══════════════════════════════════════════════════════════
// 7. BOUTONS MAGNÉTIQUES
// ═══════════════════════════════════════════════════════════

function initMagneticButtons() {
    document.querySelectorAll('.magnetic').forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width  / 2;
            const y = e.clientY - rect.top  - rect.height / 2;

            anime({
                targets: btn,
                translateX: x * 0.3,
                translateY: y * 0.3,
                duration: 400,
                easing: 'easeOutExpo',
            });
        });

        btn.addEventListener('mouseleave', () => {
            anime({
                targets: btn,
                translateX: 0,
                translateY: 0,
                duration: 600,
                easing: 'easeOutElastic(1, 0.5)',
            });
        });
    });
}

// ═══════════════════════════════════════════════════════════
// 8. TILT 3D ULTRA RÉACTIF
// ═══════════════════════════════════════════════════════════

function initTilt3D() {
    document.querySelectorAll('.card-tilt').forEach(card => {

        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x    = e.clientX - rect.left;
            const y    = e.clientY - rect.top;
            const cX   = rect.width  / 2;
            const cY   = rect.height / 2;
            const rotateX = (y - cY) / 10;
            const rotateY = (cX - x) / 10;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(20px)`;

            // Glare effect
            const glare = card.querySelector('.glare');
            if (glare) {
                const angle = Math.atan2(y - cY, x - cX) * 180 / Math.PI;
                glare.style.background = `linear-gradient(${angle + 90}deg, rgba(255,255,255,0.4) 0%, transparent 50%)`;
                glare.style.opacity = '1';
            }
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
            const glare = card.querySelector('.glare');
            if (glare) glare.style.opacity = '0';
        });
    });
}

// ═══════════════════════════════════════════════════════════
// 9. SPOTLIGHT EFFECT
// ═══════════════════════════════════════════════════════════

function initSpotlight() {
    document.querySelectorAll('.spotlight').forEach(el => {
        el.addEventListener('mousemove', (e) => {
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            el.style.setProperty('--x', `${x}px`);
            el.style.setProperty('--y', `${y}px`);
        });
    });
}

// ═══════════════════════════════════════════════════════════
// 10. COMPTEURS ANIMÉS
// ═══════════════════════════════════════════════════════════

function initCounters() {
    const counters = document.querySelectorAll('.counter');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.target);

                anime({
                    targets: { value: 0 },
                    value:   target,
                    duration: 2500,
                    easing:   'easeOutExpo',
                    round:    1,
                    update: (anim) => {
                        el.textContent = Math.round(anim.animations[0].currentValue)
                            .toLocaleString('fr-FR');
                    }
                });
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(c => observer.observe(c));
}

// ═══════════════════════════════════════════════════════════
// 11. SCROLL REVEAL — Avancé
// ═══════════════════════════════════════════════════════════

function initScrollReveal() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el       = entry.target;
                const fromY    = parseInt(el.dataset.revealY || 80);
                const duration = parseInt(el.dataset.revealDuration || 1200);

                anime({
                    targets:    el,
                    translateY: [fromY, 0],
                    opacity:    [0, 1],
                    scale:      [0.95, 1],
                    duration:   duration,
                    easing:     'easeOutExpo',
                });
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.reveal').forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
}

// ═══════════════════════════════════════════════════════════
// 12. STAGGER — Cartes en cascade
// ═══════════════════════════════════════════════════════════

function initStaggerCards() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const items = entry.target.querySelectorAll('.stagger-item');
                anime({
                    targets: items,
                    translateY: [80, 0],
                    opacity:    [0, 1],
                    scale:      [0.9, 1],
                    rotateY:    [15, 0],
                    duration:   1000,
                    delay:      anime.stagger(100, { start: 200 }),
                    easing:     'easeOutExpo',
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.stagger-container').forEach(container => {
        container.querySelectorAll('.stagger-item').forEach(item => {
            item.style.opacity = '0';
        });
        observer.observe(container);
    });
}

// ═══════════════════════════════════════════════════════════
// 13. PARALLAX AU SCROLL
// ═══════════════════════════════════════════════════════════

function initParallax() {
    let ticking = false;

    const updateParallax = () => {
        const scrollY = window.scrollY;

        document.querySelectorAll('.parallax').forEach(el => {
            const speed = parseFloat(el.dataset.speed || 0.3);
            const yPos = -(scrollY * speed);
            el.style.transform = `translate3d(0, ${yPos}px, 0)`;
        });

        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    });
}

// ═══════════════════════════════════════════════════════════
// 14. MOCKUP — Animations internes
// ═══════════════════════════════════════════════════════════

function initMockupAnimations() {

    // Barres du graphique
    setTimeout(() => {
        anime({
            targets: '.mock-bar',
            scaleY: [0, 1],
            duration: 1200,
            delay: anime.stagger(100),
            easing: 'easeOutExpo',
        });
    }, 1500);

    // Compteurs du mockup en boucle
    setInterval(() => {
        anime({
            targets: '.mock-stat',
            scale: [1, 1.1, 1],
            duration: 600,
            delay: anime.stagger(200),
            easing: 'easeOutQuad',
        });
    }, 4000);

    // Notifications qui apparaissent
    const notif = document.querySelector('.mock-notification');
    if (notif) {
        setInterval(() => {
            anime({
                targets: notif,
                translateX: [50, 0],
                opacity:    [0, 1],
                duration:   600,
                easing:     'easeOutExpo',
                complete: () => {
                    setTimeout(() => {
                        anime({
                            targets: notif,
                            opacity: [1, 0],
                            duration: 400,
                        });
                    }, 2000);
                }
            });
        }, 5000);
    }
}

// ═══════════════════════════════════════════════════════════
// 15. FAQ ANIMÉE
// ═══════════════════════════════════════════════════════════

function initFAQ() {
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
}

// ═══════════════════════════════════════════════════════════
// 16. SCROLL PROGRESS
// ═══════════════════════════════════════════════════════════

function initScrollProgress() {
    const progressBar = document.querySelector('.scroll-progress');
    if (!progressBar) return;

    window.addEventListener('scroll', () => {
        const scrollTop    = document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const progress     = (scrollTop / scrollHeight) * 100;
        progressBar.style.width = `${progress}%`;
    });
}

// ═══════════════════════════════════════════════════════════
// 17. SMOOTH SCROLL
// ═══════════════════════════════════════════════════════════

function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

// ═══════════════════════════════════════════════════════════
// 18. MARQUEE (LOGOS DÉFILANTS)
// ═══════════════════════════════════════════════════════════

function initMarquee() {
    const marquee = document.querySelector('.marquee-content');
    if (!marquee) return;

    // Dupliquer le contenu pour défilement infini
    marquee.innerHTML += marquee.innerHTML;
}
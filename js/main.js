/**
 * main.js — Dra. Guadalupe Arcuri | Psicóloga Clínica
 * Responsabilidades:
 *   1. Glassmorphism en la nav al hacer scroll
 *   2. Menú móvil (hamburguesa)
 *   3. Animaciones fade-in con IntersectionObserver
 *   4. Botón "volver arriba"
 *   5. Cerrar menú móvil al hacer click en un enlace
 *   6. Parallax leve en la foto de Sobre Mí
 *   7. Botones "magnetic" en desktop
 */

(function () {
  'use strict';

  /* ── 1. GLASSMORPHISM EN LA NAV ─────────────────────────── */
  const nav = document.querySelector('.nav');

  function handleNavScroll() {
    if (window.scrollY > 40) {
      nav.classList.add('is-scrolled');
    } else {
      nav.classList.remove('is-scrolled');
    }
  }

  if (nav) {
    window.addEventListener('scroll', handleNavScroll, { passive: true });
    // Verificar estado inicial por si la página carga con scroll
    handleNavScroll();
  }

  /* ── 2. MENÚ MÓVIL ──────────────────────────────────────── */
  const toggle = document.querySelector('.nav__toggle');
  const mobileMenu = document.querySelector('.nav__mobile-menu');

  function openMenu() {
    toggle.setAttribute('aria-expanded', 'true');
    mobileMenu.classList.add('is-open');
    // Bloquear scroll del body cuando el menú está abierto
    document.body.style.overflow = 'hidden';
  }

  function closeMenu() {
    toggle.setAttribute('aria-expanded', 'false');
    mobileMenu.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  if (toggle && mobileMenu) {
    toggle.addEventListener('click', function () {
      const isOpen = toggle.getAttribute('aria-expanded') === 'true';
      isOpen ? closeMenu() : openMenu();
    });

    // Cerrar al hacer click en cualquier enlace del menú móvil
    mobileMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', closeMenu);
    });

    // Cerrar con tecla Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        closeMenu();
        toggle.focus();
      }
    });
  }

  /* ── 3. ANIMACIONES FADE-IN CON IntersectionObserver ────── */
  const fadeElements = document.querySelectorAll('.fade-in');

  if ('IntersectionObserver' in window && fadeElements.length) {
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            // Dejar de observar el elemento una vez que apareció
            observer.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.12,      // El elemento debe ser 12% visible para activarse
        rootMargin: '0px 0px -40px 0px'  // Activar un poco antes del borde inferior
      }
    );

    fadeElements.forEach(function (el) {
      observer.observe(el);
    });
  } else {
    // Fallback: mostrar todo sin animación (navegadores que no soportan IO)
    fadeElements.forEach(function (el) {
      el.classList.add('is-visible');
    });
  }

  /* ── 4. BOTÓN VOLVER ARRIBA ─────────────────────────────── */
  const scrollTopBtn = document.querySelector('.scroll-top');

  function handleScrollTopVisibility() {
    if (window.scrollY > 400) {
      scrollTopBtn.classList.add('is-visible');
    } else {
      scrollTopBtn.classList.remove('is-visible');
    }
  }

  if (scrollTopBtn) {
    window.addEventListener('scroll', handleScrollTopVisibility, { passive: true });

    scrollTopBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ── 5. SMOOTH SCROLL PARA LINKS INTERNOS ───────────────── */
  // Compensar el height de la nav sticky al navegar a secciones
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;

      const target = document.querySelector(targetId);
      if (!target) return;

      e.preventDefault();

      const navHeight = parseInt(
        getComputedStyle(document.documentElement).getPropertyValue('--nav-height'),
        10
      ) || 72;

      const targetTop = target.getBoundingClientRect().top + window.scrollY - navHeight - 16;

      window.scrollTo({ top: targetTop, behavior: 'smooth' });
    });
  });

  /* ── 6. MARCAR EL ENLACE ACTIVO EN LA NAV AL SCROLLEAR ─── */
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav__link');
  const NAV_HEIGHT = 80;

  function updateActiveNavLink() {
    let current = '';

    sections.forEach(function (section) {
      const sectionTop = section.offsetTop - NAV_HEIGHT - 50;
      if (window.scrollY >= sectionTop) {
        current = section.getAttribute('id');
      }
    });

    navLinks.forEach(function (link) {
      link.removeAttribute('aria-current');
      if (link.getAttribute('href') === '#' + current) {
        link.setAttribute('aria-current', 'page');
      }
    });
  }

  if (sections.length && navLinks.length) {
    window.addEventListener('scroll', updateActiveNavLink, { passive: true });
  }

  const prefersMotion = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;

  /* ── 7. PARALLAX LEVE EN LA FOTO DE SOBRE MÍ ────────────── */
  if (prefersMotion) {
    const parallaxEl = document.querySelector('[data-parallax]');

    if (parallaxEl) {
      let ticking = false;

      function updateParallax() {
        const rect = parallaxEl.getBoundingClientRect();
        const progress = (window.innerHeight / 2 - (rect.top + rect.height / 2)) / window.innerHeight;
        const offset = Math.max(-1, Math.min(1, progress)) * 16;
        parallaxEl.style.transform = 'translateY(' + offset.toFixed(1) + 'px)';
        ticking = false;
      }

      window.addEventListener('scroll', function () {
        if (!ticking) {
          window.requestAnimationFrame(updateParallax);
          ticking = true;
        }
      }, { passive: true });

      updateParallax();
    }
  }

  /* ── 7. BOTONES "MAGNETIC" EN DESKTOP ────────────────────── */
  if (prefersMotion && window.matchMedia('(pointer: fine)').matches) {
    document.querySelectorAll('.btn--primary, .btn--secondary').forEach(function (btn) {
      btn.addEventListener('mousemove', function (e) {
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        btn.style.transform = 'translate(' + (x * 0.18).toFixed(1) + 'px, ' + (y * 0.35).toFixed(1) + 'px)';
      });

      btn.addEventListener('mouseleave', function () {
        btn.style.transform = '';
      });
    });
  }

})();

<?php
/**
 * index.php — Dra. Guadalupe Arcuri | Psicóloga Clínica
 * One-page website — PHP 8+
 *
 * Gestiona:
 * - Generación del token CSRF para el formulario
 * - Recuperación de datos de sesión (errores y valores previos)
 * - Mensaje de éxito tras envío (?sent=1)
 */

declare(strict_types=1);

session_start();

// ─── TOKEN CSRF ───────────────────────────────────────────────────
// Se genera uno nuevo si no existe ya en sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ─── ESTADO DEL FORMULARIO ────────────────────────────────────────
$enviado       = isset($_GET['sent']) && $_GET['sent'] === '1';
$erroresForm   = $_SESSION['form_errores'] ?? [];
$datosForm     = $_SESSION['form_datos']   ?? [];
$errorGeneral  = $_SESSION['form_error']   ?? '';

// Limpiar datos de sesión tras leerlos (solo se muestran una vez)
unset($_SESSION['form_errores'], $_SESSION['form_datos'], $_SESSION['form_error']);

/**
 * Devuelve el valor anterior de un campo si hubo un error de validación.
 * Aplica htmlspecialchars para prevenir XSS.
 */
function valorPrevio(array $datos, string $campo): string {
    return isset($datos[$campo]) ? htmlspecialchars($datos[$campo], ENT_QUOTES, 'UTF-8') : '';
}

/**
 * Devuelve la clase CSS de error para un campo si corresponde.
 */
function claseError(array $errores, string $campo): string {
    return isset($errores[$campo]) ? ' is-error' : '';
}

?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- ─── SEO ─────────────────────────────────────────────────── -->
  <title>Guadalupe Arcuri | Psicóloga Clínica Online | Turnos para toda Argentina</title>
  <meta name="description" content="Psicología clínica online para adultos, en toda Argentina. Especialista en ansiedad, vínculos y crisis vitales. Respondemos en 24hs.">
  <meta name="robots" content="index, follow">
  <meta name="author" content="Guadalupe Arcuri — Psicóloga Clínica">

  <!-- Canonical — reemplazar con la URL real en producción -->
  <link rel="canonical" href="https://www.guadalupearcuri.com.ar/">

  <!-- ─── OPEN GRAPH ───────────────────────────────────────────── -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="Guadalupe Arcuri | Psicóloga Clínica Online">
  <meta property="og:description" content="Psicología clínica online para adultos, en toda Argentina. Especialista en ansiedad, vínculos y crisis vitales.">
  <meta property="og:url" content="https://www.guadalupearcuri.com.ar/">
  <meta property="og:locale" content="es_AR">
  <!-- og:image: agregar imagen real de 1200x630px en producción -->

  <!-- ─── SCHEMA MARKUP (JSON-LD) ─────────────────────────────── -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Physician",
    "name": "Guadalupe Arcuri",
    "description": "Psicóloga Clínica especialista en ansiedad, vínculos y crisis vitales.",
    "url": "https://www.guadalupearcuri.com.ar/",
    "telephone": "+5491112345678",
    "email": "guadalupe.arcuri@psicologia.com",
    "areaServed": "AR",
    "availableChannel": {
      "@type": "ServiceChannel",
      "serviceType": "Videoconsulta"
    },
    "openingHours": "Mo,Tu,We,Th,Fr 09:00-19:00",
    "priceRange": "$$",
    "medicalSpecialty": "Psychiatry",
    "availableService": [
      {"@type": "MedicalTherapy", "name": "Terapia Cognitivo-Conductual"},
      {"@type": "MedicalTherapy", "name": "Psicología Clínica para Adultos"}
    ]
  }
  </script>

  <!-- ─── FUENTES TIPOGRÁFICAS ─────────────────────────────────── -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet"
  >

  <!-- ─── ICONOS LUCIDE (CDN) ──────────────────────────────────── -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js" defer></script>

  <!-- ─── ESTILOS ──────────────────────────────────────────────── -->
  <link rel="stylesheet" href="css/style.css">
</head>

<body>

  <!-- Skip link para accesibilidad (teclado / lectores de pantalla) -->
  <a class="skip-link" href="#main-content">Saltar al contenido principal</a>

  <!-- ═══════════════════════════════════════════════════════════
       NAVEGACIÓN
  ════════════════════════════════════════════════════════════ -->
  <header role="banner">
    <nav class="nav" aria-label="Navegación principal">
      <div class="container">
        <div class="nav__inner">

          <!-- Logo -->
          <a href="#inicio" class="nav__logo" aria-label="Dra. Guadalupe Arcuri — Psicóloga Clínica — Ir al inicio">
            <span>Dra. Guadalupe Arcuri</span> | Psicóloga Clínica
          </a>

          <!-- Menú desktop -->
          <ul class="nav__menu" role="list">
            <li><a href="#sobre-mi"     class="nav__link">Sobre mí</a></li>
            <li><a href="#servicios"    class="nav__link">Servicios</a></li>
            <li><a href="#como-trabajo" class="nav__link">Cómo trabajo</a></li>
            <li><a href="#contacto"     class="nav__link">Contacto</a></li>
          </ul>

          <!-- CTA desktop -->
          <a href="#contacto" class="btn btn--primary" style="display:none" id="nav-cta-desktop">
            <i data-lucide="calendar" aria-hidden="true" width="16" height="16"></i>
            Pedir turno
          </a>

          <!-- Botón hamburguesa (mobile) -->
          <button
            class="nav__toggle"
            aria-label="Abrir menú de navegación"
            aria-expanded="false"
            aria-controls="mobile-menu"
          >
            <span class="nav__toggle-line" aria-hidden="true"></span>
            <span class="nav__toggle-line" aria-hidden="true"></span>
            <span class="nav__toggle-line" aria-hidden="true"></span>
          </button>

        </div>
      </div>

      <!-- Menú móvil desplegable -->
      <nav
        id="mobile-menu"
        class="nav__mobile-menu"
        aria-label="Menú móvil"
        role="navigation"
      >
        <a href="#sobre-mi"     class="nav__mobile-link">Sobre mí</a>
        <a href="#servicios"    class="nav__mobile-link">Servicios</a>
        <a href="#como-trabajo" class="nav__mobile-link">Cómo trabajo</a>
        <a href="#contacto"     class="nav__mobile-link">Contacto</a>
        <a href="#contacto" class="btn btn--primary nav__mobile-cta" style="margin-top: 0.5rem;">
          <i data-lucide="calendar" aria-hidden="true" width="16" height="16"></i>
          Pedir turno
        </a>
      </nav>

    </nav>
  </header>

  <!-- ═══════════════════════════════════════════════════════════
       CONTENIDO PRINCIPAL
  ════════════════════════════════════════════════════════════ -->
  <main id="main-content">

    <!-- ─── HERO ─────────────────────────────────────────────── -->
    <section id="inicio" class="hero" aria-label="Presentación">
      <div class="container">
        <div class="hero__inner">

          <!-- Columna de texto -->
          <div class="hero__content">
            <p class="hero__eyebrow" aria-hidden="true">
              <i data-lucide="heart" width="14" height="14"></i>
              Psicología Clínica para adultos
            </p>

            <!-- H1 único de la página — clave para SEO -->
            <h1 class="hero__title">
              Un espacio seguro para entenderte y avanzar hacia el bienestar que <em>merecés.</em>
            </h1>

            <p class="hero__subtitle">
              Psicología clínica para adultos — 100% online, para todo el país. Agendá tu primera consulta hoy.
            </p>

            <div class="hero__actions">
              <a href="#contacto" class="btn btn--primary">
                <i data-lucide="calendar" aria-hidden="true" width="18" height="18"></i>
                Quiero pedir un turno
              </a>
              <a href="#sobre-mi" class="btn btn--secondary">
                Conocé mi enfoque
                <i data-lucide="arrow-down" aria-hidden="true" width="16" height="16"></i>
              </a>
            </div>

            <!-- Trust badges -->
            <div class="hero__trust" role="list" aria-label="Credenciales">
              <div class="hero__badge" role="listitem">
                <i data-lucide="users" aria-hidden="true" width="14" height="14"></i>
                +200 pacientes acompañados
              </div>
              <div class="hero__badge" role="listitem">
                <i data-lucide="graduation-cap" aria-hidden="true" width="14" height="14"></i>
                Lic. en Psicología — UBA
              </div>
              <div class="hero__badge" role="listitem">
                <i data-lucide="shield-check" aria-hidden="true" width="14" height="14"></i>
                Mat. 12345
              </div>
            </div>
          </div>

          <!-- Columna visual -->
          <div class="hero__visual" aria-hidden="true">
            <div class="hero__image-wrap">
              <!-- Reemplazar este div con una etiqueta <img> real cuando haya foto profesional -->
              <div class="hero__image-placeholder">
                <i data-lucide="user-round" width="64" height="64"></i>
                <span>Foto profesional<br>de la Dra. Arcuri</span>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ─── SOBRE MÍ ──────────────────────────────────────────── -->
    <section id="sobre-mi" class="section" aria-labelledby="sobre-mi-title">
      <div class="container">
        <div class="about__grid">

          <!-- Foto -->
          <div class="about__photo-wrap fade-in">
            <img class="about__photo-placeholder" src="img/sobre-mi.jpg" alt="Dra. Guadalupe Arcuri, psicóloga clínica">

            <!-- Sticker "8 años de experiencia" -->
            <div class="about__sticker" aria-hidden="true">
              <strong>8</strong>
              años de<br>experiencia
            </div>
          </div>

          <!-- Contenido de texto -->
          <div class="about__content fade-in">
            <p class="section__eyebrow">Sobre mí</p>

            <h2 id="sobre-mi-title">Hola, soy Guada.</h2>

            <p>
              Elegí la psicología porque creo que entenderse a uno mismo es una de las formas más profundas de cambiar la propia vida. Desde hace 8 años acompaño a personas que atraviesan momentos difíciles, que buscan conocerse mejor, o que simplemente sienten que algo no está funcionando como quisieran.
            </p>
            <p>
              Me especializo en ansiedad, vínculos y crisis vitales, trabajando desde un enfoque cognitivo-conductual integrativo. Mi forma de trabajar combina rigor clínico con calidez: cada persona es única, y el proceso terapéutico se construye a su medida.
            </p>
            <p>
              Soy Licenciada en Psicología por la UBA, con formación de posgrado en Terapia Cognitivo-Conductual. Trabajo de forma ética, con confidencialidad absoluta.
            </p>

            <!-- Credenciales -->
            <div class="about__credentials" role="list" aria-label="Formación académica">
              <span class="about__credential" role="listitem">
                <i data-lucide="graduation-cap" width="14" height="14" aria-hidden="true"></i>
                Universidad de Buenos Aires
              </span>
              <span class="about__credential" role="listitem">
                <i data-lucide="brain" width="14" height="14" aria-hidden="true"></i>
                Especialización en TCC
              </span>
              <span class="about__credential" role="listitem">
                <i data-lucide="award" width="14" height="14" aria-hidden="true"></i>
                8 años de experiencia
              </span>
            </div>

            <a href="#contacto" class="btn btn--primary" style="align-self: flex-start; margin-top: 0.5rem;">
              <i data-lucide="calendar" aria-hidden="true" width="18" height="18"></i>
              Hablemos — agendá una consulta
            </a>
          </div>

        </div>
      </div>
    </section>

    <!-- ─── SERVICIOS ─────────────────────────────────────────── -->
    <section id="servicios" class="section section--alt" aria-labelledby="servicios-title">
      <div class="container">

        <header class="section__header fade-in">
          <p class="section__eyebrow">Servicios</p>
          <h2 id="servicios-title" class="section__title">¿En qué te puedo acompañar?</h2>
          <p class="section__subtitle">
            Cada proceso es único. Estos son los principales motivos de consulta con los que trabajo.
          </p>
        </header>

        <ul class="services__grid" role="list">

          <li class="service-card fade-in" role="listitem">            <h3 class="service-card__title">Ansiedad y estrés</h3>
            <p class="service-card__desc">
              Cuando la mente no para, el cuerpo lo siente. Herramientas reales para el día a día.
            </p>
          </li>

          <li class="service-card fade-in" role="listitem">            <h3 class="service-card__title">Tristeza y depresión</h3>
            <p class="service-card__desc">
              Un espacio para explorar lo que sentís, sin juicio y a tu propio ritmo.
            </p>
          </li>

          <li class="service-card fade-in" role="listitem">            <h3 class="service-card__title">Relaciones y vínculos</h3>
            <p class="service-card__desc">
              Entendemos juntos los patrones que se repiten y cómo cambiarlos.
            </p>
          </li>

          <li class="service-card fade-in" role="listitem">            <h3 class="service-card__title">Crisis y transiciones vitales</h3>
            <p class="service-card__desc">
              Los momentos de quiebre también pueden ser puntos de partida.
            </p>
          </li>

          <li class="service-card fade-in" role="listitem">            <h3 class="service-card__title">Autoconocimiento personal</h3>
            <p class="service-card__desc">
              No hace falta estar en crisis. Muchas personas vienen para conocerse mejor.
            </p>
          </li>

        </ul>

      </div>
    </section>

    <!-- ─── CÓMO TRABAJO — STEPPER ────────────────────────────── -->
    <section id="como-trabajo" class="section" aria-labelledby="como-trabajo-title">
      <div class="container">

        <header class="section__header fade-in">
          <p class="section__eyebrow">Mi metodología</p>
          <h2 id="como-trabajo-title" class="section__title">Cómo es el proceso</h2>
          <p class="section__subtitle">
            Cada etapa está pensada para que te sientas acompañado/a y seguro/a durante todo el camino.
          </p>
        </header>

        <ol class="how__grid" role="list" aria-label="Pasos del proceso terapéutico">

          <li class="how__step fade-in">
            <div class="how__step-number" aria-hidden="true">1</div>
            <h3 class="how__step-title">Primera consulta</h3>
            <p class="how__step-desc">Nos conocemos. Sin compromisos. 60 minutos para hablar de lo que te trajo hasta acá.</p>
          </li>

          <li class="how__step fade-in">
            <div class="how__step-number" aria-hidden="true">2</div>
            <h3 class="how__step-title">Evaluación</h3>
            <p class="how__step-desc">Exploramos tu situación en profundidad y definimos objetivos terapéuticos juntos.</p>
          </li>

          <li class="how__step fade-in">
            <div class="how__step-number" aria-hidden="true">3</div>
            <h3 class="how__step-title">Proceso terapéutico</h3>
            <p class="how__step-desc">Sesiones semanales en un espacio completamente confidencial y a tu medida.</p>
          </li>

          <li class="how__step fade-in">
            <div class="how__step-number" aria-hidden="true">4</div>
            <h3 class="how__step-title">Seguimiento</h3>
            <p class="how__step-desc">El proceso se adapta a tu evolución. No hay un tiempo fijo: avanzamos a tu ritmo.</p>
          </li>

        </ol>

      </div>
    </section>

    <!-- ─── TESTIMONIOS ───────────────────────────────────────── -->
    <section id="testimonios" class="section section--sage" aria-labelledby="testimonios-title">
      <div class="container">

        <header class="section__header fade-in">
          <p class="section__eyebrow">Testimonios</p>
          <h2 id="testimonios-title" class="section__title">Lo que dicen quienes ya dieron el paso</h2>
        </header>

        <ul class="testimonials__grid" role="list">

          <li class="testimonial-card fade-in" role="listitem">
            <div class="testimonial-card__stars" aria-label="5 estrellas">
              <?php for ($i = 0; $i < 5; $i++): ?>
                <i data-lucide="star" width="16" height="16" aria-hidden="true" style="fill:currentColor"></i>
              <?php endfor; ?>
            </div>
            <blockquote>
              <p class="testimonial-card__quote">
                "Llegar a terapia fue difícil para mí. Pero desde la primera sesión sentí que podía hablar sin que me juzgaran."
              </p>
            </blockquote>
            <div class="testimonial-card__author">
              <div class="testimonial-card__avatar" aria-hidden="true">M</div>
              <div>
                <p class="testimonial-card__name">M.G.</p>
                <p class="testimonial-card__meta">34 años — Paciente</p>
              </div>
            </div>
          </li>

          <li class="testimonial-card fade-in" role="listitem">
            <div class="testimonial-card__stars" aria-label="5 estrellas">
              <?php for ($i = 0; $i < 5; $i++): ?>
                <i data-lucide="star" width="16" height="16" aria-hidden="true" style="fill:currentColor"></i>
              <?php endfor; ?>
            </div>
            <blockquote>
              <p class="testimonial-card__quote">
                "El proceso fue exactamente lo que necesitaba. Aprendí a escucharme de una manera que no sabía que era posible."
              </p>
            </blockquote>
            <div class="testimonial-card__author">
              <div class="testimonial-card__avatar" aria-hidden="true">L</div>
              <div>
                <p class="testimonial-card__name">L.R.</p>
                <p class="testimonial-card__meta">28 años — Paciente</p>
              </div>
            </div>
          </li>

          <li class="testimonial-card fade-in" role="listitem">
            <div class="testimonial-card__stars" aria-label="5 estrellas">
              <?php for ($i = 0; $i < 5; $i++): ?>
                <i data-lucide="star" width="16" height="16" aria-hidden="true" style="fill:currentColor"></i>
              <?php endfor; ?>
            </div>
            <blockquote>
              <p class="testimonial-card__quote">
                "Las sesiones online fueron una de las mejores decisiones que tomé. La comodidad de estar en casa ayudó mucho."
              </p>
            </blockquote>
            <div class="testimonial-card__author">
              <div class="testimonial-card__avatar" aria-hidden="true">P</div>
              <div>
                <p class="testimonial-card__name">P.V.</p>
                <p class="testimonial-card__meta">41 años — Paciente</p>
              </div>
            </div>
          </li>

        </ul>

      </div>
    </section>

    <!-- ─── FAQ ───────────────────────────────────────────────── -->
    <section id="faq" class="section" aria-labelledby="faq-title">
      <div class="container">

        <header class="section__header fade-in">
          <p class="section__eyebrow">Preguntas frecuentes</p>
          <h2 id="faq-title" class="section__title">Todo lo que querés saber antes de empezar</h2>
        </header>

        <!-- Acordeón con elementos <details>/<summary> — CSS puro, sin JS -->
        <div class="faq__list fade-in" role="list">

          <details class="faq__item" role="listitem">
            <summary class="faq__question">
              ¿Cómo sé si necesito ir al psicólogo?
              <span class="faq__icon" aria-hidden="true">
                <i data-lucide="plus" width="16" height="16"></i>
              </span>
            </summary>
            <div class="faq__answer">
              <p>
                No hace falta estar en una crisis grave para buscar ayuda profesional. Si sentís que algo no está bien, que estás más ansioso/a de lo habitual, que tus vínculos te generan malestar, o simplemente que querés conocerte mejor, eso es suficiente razón para empezar. La terapia no es solo para "casos graves": es para cualquier persona que quiera crecer y mejorar su calidad de vida.
              </p>
            </div>
          </details>

          <details class="faq__item" role="listitem">
            <summary class="faq__question">
              ¿Cuánto dura cada sesión?
              <span class="faq__icon" aria-hidden="true">
                <i data-lucide="plus" width="16" height="16"></i>
              </span>
            </summary>
            <div class="faq__answer">
              <p>
                Cada sesión dura 50 minutos. La primera consulta es de 60 minutos para poder hacer una evaluación más completa de tu situación y definir juntos los objetivos del proceso.
              </p>
            </div>
          </details>

          <details class="faq__item" role="listitem">
            <summary class="faq__question">
              ¿Cuánto tiempo dura el proceso terapéutico?
              <span class="faq__icon" aria-hidden="true">
                <i data-lucide="plus" width="16" height="16"></i>
              </span>
            </summary>
            <div class="faq__answer">
              <p>
                No hay un tiempo estándar. Depende de los objetivos, la profundidad de la problemática y el ritmo de cada persona. Algunos procesos duran algunos meses, otros se extienden por más tiempo. Lo importante es que el avance sea real y que vos lo puedas sentir. Siempre trabajamos con claridad sobre en qué etapa estamos.
              </p>
            </div>
          </details>

          <details class="faq__item" role="listitem">
            <summary class="faq__question">
              ¿Las sesiones online son igual de efectivas?
              <span class="faq__icon" aria-hidden="true">
                <i data-lucide="plus" width="16" height="16"></i>
              </span>
            </summary>
            <div class="faq__answer">
              <p>
                Sí. Hay amplia evidencia científica que respalda la efectividad de la psicoterapia online. El vínculo terapéutico se construye igual, y muchos pacientes valoran la comodidad y la posibilidad de hacer la sesión desde su espacio personal. Lo único que necesitás es una conexión estable y un lugar donde puedas hablar con privacidad.
              </p>
            </div>
          </details>

          <details class="faq__item" role="listitem">
            <summary class="faq__question">
              ¿Qué pasa con la confidencialidad?
              <span class="faq__icon" aria-hidden="true">
                <i data-lucide="plus" width="16" height="16"></i>
              </span>
            </summary>
            <div class="faq__answer">
              <p>
                Todo lo que se habla en sesión es estrictamente confidencial, amparado por el secreto profesional y el Código de Ética del COPSI. La única excepción legal es en situaciones de riesgo cierto e inminente para la vida, propias o ajenas. Podés hablar con total libertad y confianza.
              </p>
            </div>
          </details>

          <details class="faq__item" role="listitem">
            <summary class="faq__question">
              ¿Trabajás con obras sociales?
              <span class="faq__icon" aria-hidden="true">
                <i data-lucide="plus" width="16" height="16"></i>
              </span>
            </summary>
            <div class="faq__answer">
              <p>
                Actualmente trabajo de forma particular. Podés consultar con tu obra social o prepaga sobre la posibilidad de reintegro parcial de honorarios, ya que muchas contemplan esta opción. Si necesitás más información, no dudes en preguntarme al momento de agendar tu consulta.
              </p>
            </div>
          </details>

        </div>

      </div>
    </section>

    <!-- ─── CONTACTO ──────────────────────────────────────────── -->
    <section id="contacto" class="section section--alt" aria-labelledby="contacto-title">
      <div class="container">

        <header class="section__header fade-in">
          <p class="section__eyebrow">Contacto</p>
          <h2 id="contacto-title" class="section__title">¿Listo/a para dar el primer paso?</h2>
          <p class="section__subtitle">
            Respondemos todos los mensajes en menos de 24 horas hábiles.
          </p>
        </header>

        <div class="contact__grid">

          <!-- Columna izquierda — información de contacto -->
          <div class="fade-in">
            <h3 class="contact__info-title">Medios de contacto</h3>
            <p class="contact__info-text">
              Podés escribirme por el formulario, por WhatsApp o por email. Contestaré a la brevedad.
            </p>

            <ul class="contact__channels" role="list">
              <li class="contact__channel">
                <div class="contact__channel-icon" aria-hidden="true">
                  <i data-lucide="message-circle" width="22" height="22"></i>
                </div>
                <div>
                  <strong>WhatsApp</strong>
                  <a
                    href="https://wa.me/5491112345678?text=Hola%20Guada%2C%20me%20gustar%C3%ADa%20agendar%20una%20consulta."
                    aria-label="Escribir por WhatsApp a +54 9 11 1234-5678"
                    style="color: var(--color-sage-dark); font-size: 0.9375rem;"
                  >+54 9 11 1234-5678</a>
                </div>
              </li>

              <li class="contact__channel">
                <div class="contact__channel-icon" aria-hidden="true">
                  <i data-lucide="mail" width="22" height="22"></i>
                </div>
                <div>
                  <strong>Email</strong>
                  <a
                    href="mailto:guadalupe.arcuri@psicologia.com"
                    style="color: var(--color-sage-dark); font-size: 0.9375rem;"
                  >guadalupe.arcuri@psicologia.com</a>
                </div>
              </li>

              <li class="contact__channel">
                <div class="contact__channel-icon" aria-hidden="true">
                  <i data-lucide="clock" width="22" height="22"></i>
                </div>
                <div>
                  <strong>Horarios de atención</strong>
                  <span>Lunes a viernes, 9 a 19 hs.</span>
                </div>
              </li>

              <li class="contact__channel">
                <div class="contact__channel-icon" aria-hidden="true">
                  <i data-lucide="monitor" width="22" height="22"></i>
                </div>
                <div>
                  <strong>Zona de atención</strong>
                  <span>Atención 100% online — Para todo el país</span>
                </div>
              </li>
            </ul>

          </div>

          <!-- Columna derecha — formulario -->
          <div class="fade-in">
            <div class="contact-form">
              <h3 class="contact-form__title">Enviame un mensaje</h3>

              <?php if ($enviado): ?>
                <!-- Mensaje de éxito tras envío exitoso -->
                <div class="form__success is-visible" role="alert" aria-live="polite">
                  <i data-lucide="check-circle" width="22" height="22" aria-hidden="true"></i>
                  ¡Mensaje recibido! Te responderé en menos de 24hs hábiles.
                </div>
              <?php endif; ?>

              <?php if (!empty($errorGeneral)): ?>
                <!-- Error general del sistema -->
                <div class="form__success is-visible" role="alert" aria-live="polite"
                  style="background-color: #fdf2f2; border-color: #d64045; color: #d64045;">
                  <i data-lucide="alert-circle" width="22" height="22" aria-hidden="true"></i>
                  <?= htmlspecialchars($errorGeneral, ENT_QUOTES, 'UTF-8') ?>
                </div>
              <?php endif; ?>

              <form
                method="POST"
                action="contact.php"
                novalidate
                aria-label="Formulario de contacto"
              >
                <!-- Token CSRF oculto — protección contra ataques cross-site -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <!-- Nombre -->
                <div class="form__group">
                  <label class="form__label" for="nombre">
                    Nombre y apellido
                    <abbr title="Campo obligatorio" aria-label="requerido">*</abbr>
                  </label>
                  <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    class="form__control<?= claseError($erroresForm, 'nombre') ?>"
                    placeholder="Ej: María González"
                    value="<?= valorPrevio($datosForm, 'nombre') ?>"
                    required
                    autocomplete="name"
                    maxlength="100"
                    aria-required="true"
                    <?= isset($erroresForm['nombre']) ? 'aria-describedby="error-nombre"' : '' ?>
                  >
                  <?php if (isset($erroresForm['nombre'])): ?>
                    <p class="form__error" id="error-nombre" role="alert">
                      <?= htmlspecialchars($erroresForm['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="form__group">
                  <label class="form__label" for="email">
                    Email
                    <abbr title="Campo obligatorio" aria-label="requerido">*</abbr>
                  </label>
                  <input
                    type="email"
                    id="email"
                    name="email"
                    class="form__control<?= claseError($erroresForm, 'email') ?>"
                    placeholder="tucorreo@ejemplo.com"
                    value="<?= valorPrevio($datosForm, 'email') ?>"
                    required
                    autocomplete="email"
                    maxlength="254"
                    aria-required="true"
                    <?= isset($erroresForm['email']) ? 'aria-describedby="error-email"' : '' ?>
                  >
                  <?php if (isset($erroresForm['email'])): ?>
                    <p class="form__error" id="error-email" role="alert">
                      <?= htmlspecialchars($erroresForm['email'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  <?php endif; ?>
                </div>

                <!-- Teléfono (opcional) -->
                <div class="form__group">
                  <label class="form__label" for="telefono">
                    Teléfono
                    <span style="font-weight:400; color: var(--color-text-light); font-size: 0.8125rem;">(opcional)</span>
                  </label>
                  <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    class="form__control<?= claseError($erroresForm, 'telefono') ?>"
                    placeholder="+54 9 11 XXXX-XXXX"
                    value="<?= valorPrevio($datosForm, 'telefono') ?>"
                    autocomplete="tel"
                    maxlength="25"
                    <?= isset($erroresForm['telefono']) ? 'aria-describedby="error-telefono"' : '' ?>
                  >
                  <?php if (isset($erroresForm['telefono'])): ?>
                    <p class="form__error" id="error-telefono" role="alert">
                      <?= htmlspecialchars($erroresForm['telefono'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  <?php endif; ?>
                </div>

                <!-- Modalidad (requerida) -->
                <input type="hidden" id="modalidad" name="modalidad" value="Online">

                <!-- Mensaje (opcional) -->
                <div class="form__group">
                  <label class="form__label" for="mensaje">
                    Mensaje
                    <span style="font-weight:400; color: var(--color-text-light); font-size: 0.8125rem;">(opcional)</span>
                  </label>
                  <textarea
                    id="mensaje"
                    name="mensaje"
                    class="form__control<?= claseError($erroresForm, 'mensaje') ?>"
                    placeholder="Contame brevemente qué te trae por acá, o simplemente pedí turno..."
                    rows="4"
                    maxlength="2000"
                    <?= isset($erroresForm['mensaje']) ? 'aria-describedby="error-mensaje"' : '' ?>
                  ><?= valorPrevio($datosForm, 'mensaje') ?></textarea>
                  <?php if (isset($erroresForm['mensaje'])): ?>
                    <p class="form__error" id="error-mensaje" role="alert">
                      <?= htmlspecialchars($erroresForm['mensaje'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  <?php endif; ?>
                </div>

                <!-- Botón de envío -->
                <button type="submit" class="btn btn--primary" style="width: 100%; justify-content: center;">
                  <i data-lucide="send" aria-hidden="true" width="18" height="18"></i>
                  Enviar mensaje
                </button>

                <!-- Aviso de privacidad -->
                <p class="form__privacy">
                  <i data-lucide="lock" width="12" height="12" aria-hidden="true" style="display:inline; vertical-align:middle; margin-right:4px;"></i>
                  Tu información es confidencial y nunca será compartida con terceros.
                  Todo lo que compartas está amparado por el secreto profesional.
                </p>

              </form>
            </div>
          </div>

        </div>
      </div>
    </section>

  </main>

  <!-- ═══════════════════════════════════════════════════════════
       FOOTER
  ════════════════════════════════════════════════════════════ -->
  <footer class="footer" role="contentinfo">
    <div class="container">
      <div class="footer__inner">

        <!-- Marca y copyright -->
        <div>
          <p class="footer__brand-name">Guadalupe Arcuri</p>
          <p class="footer__brand-sub">Psicóloga Clínica</p>
          <p class="footer__brand-sub" style="margin-bottom: 1rem;">
            Lic. en Psicología — Mat. 12345 — COPSI
          </p>
          <p class="footer__copyright">
            &copy; <?= date('Y') ?> Guadalupe Arcuri. Todos los derechos reservados.
          </p>
        </div>

        <!-- Aviso de crisis -->
        <div class="footer__crisis" role="note" aria-label="Información de emergencia">
          <strong>
            <i data-lucide="phone-call" width="14" height="14" aria-hidden="true" style="display:inline; vertical-align:middle; margin-right:6px;"></i>
            Si estás en una situación de emergencia
          </strong>
          Si estás atravesando una crisis y necesitás ayuda inmediata, llamá al
          <a href="tel:135" aria-label="Llamar al Centro de Asistencia al Suicida, número 135, gratuito">135</a>
          — Centro de Asistencia al Suicida (gratuito, las 24 hs, todo el país).
        </div>

      </div>
    </div>
  </footer>

  <!-- Botón "volver arriba" -->
  <button
    class="scroll-top"
    aria-label="Volver al inicio de la página"
    title="Volver arriba"
  >
    <i data-lucide="arrow-up" width="20" height="20" aria-hidden="true"></i>
  </button>

  <!-- ─── JAVASCRIPT ────────────────────────────────────────────── -->
  <script>
    // Mostrar el CTA de desktop una vez que la página cargó
    // (lo mostramos con display:none para evitar FOUC sin JS)
    document.addEventListener('DOMContentLoaded', function () {
      var navCta = document.getElementById('nav-cta-desktop');
      if (navCta && window.innerWidth >= 900) {
        navCta.style.display = 'inline-flex';
      }
      window.addEventListener('resize', function () {
        if (navCta) {
          navCta.style.display = window.innerWidth >= 900 ? 'inline-flex' : 'none';
        }
      });
    });
  </script>
  <script src="js/main.js" defer></script>

  <!-- Inicializar iconos Lucide luego de que se cargue el DOM -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    });
  </script>

</body>
</html>

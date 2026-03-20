/**
 * server.js — Servidor Node.js para el sitio de Dra. Lupita Arcuri
 * Sirve el sitio estático y procesa el formulario de contacto.
 *
 * Uso: node server.js
 * Luego abrir: http://localhost:8080
 */

const express    = require('express');
const nodemailer = require('nodemailer');
const path       = require('path');

const app  = express();
const PORT = 8080;

// ─── Middlewares ──────────────────────────────────────────────
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname)));

// ─── Configuración de email ───────────────────────────────────
// IMPORTANTE: Reemplazá estos datos con los reales antes de producción.
// Para Gmail: activá "Contraseñas de aplicación" en tu cuenta Google.
const EMAIL_CONFIG = {
  destinatario: 'lupita.arcuri@psicologia.com',  // <-- email de Lupita
  remitente:    'lupita.arcuri@psicologia.com',  // <-- mismo email o uno de envío
  password:     'TU_PASSWORD_DE_APP_GMAIL',       // <-- contraseña de app
};

// ─── Ruta principal ───────────────────────────────────────────
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'index.html'));
});

// ─── Endpoint del formulario de contacto ─────────────────────
app.post('/api/contact', async (req, res) => {
  const { nombre, email, telefono, modalidad, mensaje } = req.body;

  // Validación básica
  if (!nombre || !email || !modalidad) {
    return res.status(400).json({ ok: false, error: 'Campos requeridos faltantes.' });
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    return res.status(400).json({ ok: false, error: 'El email no tiene un formato válido.' });
  }

  // Configurar transporter de Nodemailer
  // Para probar localmente sin email real, usamos Ethereal (email de prueba)
  let transporter;
  let previewUrl = null;

  if (EMAIL_CONFIG.password === 'TU_PASSWORD_DE_APP_GMAIL') {
    // Modo prueba: usar Ethereal (captura el email sin enviarlo realmente)
    const testAccount = await nodemailer.createTestAccount();
    transporter = nodemailer.createTransport({
      host: 'smtp.ethereal.email',
      port: 587,
      auth: {
        user: testAccount.user,
        pass: testAccount.pass,
      },
    });
  } else {
    // Modo producción: Gmail real
    transporter = nodemailer.createTransport({
      service: 'gmail',
      auth: {
        user: EMAIL_CONFIG.remitente,
        pass: EMAIL_CONFIG.password,
      },
    });
  }

  // Construir el email
  const mailOptions = {
    from:    `"Sitio Web Lupita Arcuri" <${EMAIL_CONFIG.remitente}>`,
    to:      EMAIL_CONFIG.destinatario,
    replyTo: `"${nombre}" <${email}>`,
    subject: `Nuevo contacto desde el sitio web — ${nombre}`,
    text: `
Nuevo mensaje de contacto recibido desde el sitio web.
${'─'.repeat(50)}

Nombre:    ${nombre}
Email:     ${email}
Teléfono:  ${telefono || 'No informado'}
Modalidad: ${modalidad}

Mensaje:
${mensaje || 'Sin mensaje adicional.'}

${'─'.repeat(50)}
Enviado el: ${new Date().toLocaleString('es-AR')}
    `.trim(),
    html: `
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; background: #faf7f2; border-radius: 12px;">
        <h2 style="color: #5a7a6b; border-bottom: 2px solid #eaf2ee; padding-bottom: 12px;">
          Nuevo contacto desde el sitio web
        </h2>
        <table style="width:100%; border-collapse: collapse; margin: 16px 0;">
          <tr><td style="padding: 8px; font-weight: bold; color: #5c5c5c; width: 120px;">Nombre</td><td style="padding: 8px; color: #2c2c2c;">${nombre}</td></tr>
          <tr style="background:#fff;"><td style="padding: 8px; font-weight: bold; color: #5c5c5c;">Email</td><td style="padding: 8px;"><a href="mailto:${email}" style="color:#7c9e8f;">${email}</a></td></tr>
          <tr><td style="padding: 8px; font-weight: bold; color: #5c5c5c;">Teléfono</td><td style="padding: 8px; color: #2c2c2c;">${telefono || 'No informado'}</td></tr>
          <tr style="background:#fff;"><td style="padding: 8px; font-weight: bold; color: #5c5c5c;">Modalidad</td><td style="padding: 8px; color: #2c2c2c;">${modalidad}</td></tr>
        </table>
        ${mensaje ? `
        <div style="background: #fff; border-left: 4px solid #7c9e8f; padding: 16px; border-radius: 0 8px 8px 0; margin-top: 16px;">
          <p style="font-weight: bold; color: #5c5c5c; margin: 0 0 8px;">Mensaje:</p>
          <p style="color: #2c2c2c; margin: 0; white-space: pre-wrap;">${mensaje}</p>
        </div>` : ''}
        <p style="color: #8c8c8c; font-size: 12px; margin-top: 24px;">
          Enviado el ${new Date().toLocaleString('es-AR')} desde lupitaarcuri.com
        </p>
      </div>
    `,
  };

  try {
    const info = await transporter.sendMail(mailOptions);

    // Si estamos en modo prueba, mostrar URL de preview
    if (EMAIL_CONFIG.password === 'TU_PASSWORD_DE_APP_GMAIL') {
      previewUrl = nodemailer.getTestMessageUrl(info);
      console.log('\n📧 EMAIL DE PRUEBA ENVIADO');
      console.log('   Ver email en:', previewUrl);
      console.log('   (En producción, configura tu Gmail real en EMAIL_CONFIG)\n');
    }

    return res.json({
      ok: true,
      message: '¡Mensaje enviado! Te responderé en menos de 24hs.',
      previewUrl: previewUrl || null,
    });

  } catch (err) {
    console.error('Error enviando email:', err.message);
    return res.status(500).json({
      ok: false,
      error: 'Hubo un problema al enviar el mensaje. Intentá de nuevo o escribime por WhatsApp.',
    });
  }
});

// ─── Iniciar servidor ─────────────────────────────────────────
app.listen(PORT, () => {
  console.log(`\n✓ Servidor corriendo en http://localhost:${PORT}`);
  console.log('  Presiona Ctrl+C para detener\n');
});

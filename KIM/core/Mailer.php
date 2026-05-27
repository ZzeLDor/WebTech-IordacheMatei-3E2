<?php

namespace Core;

// Serviciu de trimitere emailuri prin protocolul SMTP folosind socket-uri direct in PHP (fara librarii externe)
class Mailer {

    // Setari de conexiune pentru serverul SMTP (implicit configurat pentru Mailtrap sandbox)
    private static $smtpHost     = 'sandbox.smtp.mailtrap.io';
    private static $smtpPort     = 2525;
    private static $smtpUser     = 'f7776f391058a6'; 
    private static $smtpPass     = 'e9bb8676985cd7'; 
    private static $fromEmail    = 'no-reply@kim-fitness.ro';
    private static $fromName     = 'KIM Fitness Management';

    // Trimite un email impachetat in template-ul HTML al aplicatiei
    public static function send($to, $name, $subject, $body) {
        try {
            $htmlBody = self::wrapTemplate($name, $body);
            return self::sendViaSMTP($to, $name, $subject, $htmlBody);
        } catch (\Exception $e) {
            error_log('[KIM Mailer] Exception: ' . $e->getMessage());
            return false;
        }
    }

    // Citeste raspunsul primit de la serverul SMTP linie cu linie
    private static function smtpRead($socket) {
        $code = '';
        $line = '';
        do {
            $line = fgets($socket, 512);
            if ($line === false) break;
            $code = substr($line, 0, 3);
            error_log('[KIM SMTP] << ' . rtrim($line));
        } while (strlen($line) > 3 && $line[3] === '-'); 
        return $code;
    }

    // Scrie o comanda SMTP catre socket-ul deschis
    private static function smtpWrite($socket, $cmd, $label = null) {
        $display = $label ?? rtrim($cmd);
        error_log('[KIM SMTP] >> ' . $display);
        fwrite($socket, $cmd);
    }

    // Executa interactiunea low-level cu serverul SMTP (EHLO, AUTH LOGIN, MAIL FROM, RCPT TO, DATA, QUIT)
    private static function sendViaSMTP($to, $toName, $subject, $htmlBody) {
        $host     = self::$smtpHost;
        $port     = self::$smtpPort;
        $userB64  = base64_encode(self::$smtpUser);
        $passB64  = base64_encode(self::$smtpPass);
        $from     = self::$fromEmail;
        $fromName = self::$fromName;

        error_log("[KIM Mailer] Connecting to {$host}:{$port} ...");

        $boundary       = '----=_Part_' . md5(uniqid('', true));
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $plainText      = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $message  = "Date: " . date('r') . "\r\n";
        $message .= "From: {$fromName} <{$from}>\r\n";
        $message .= "To: {$toName} <{$to}>\r\n";
        $message .= "Subject: {$encodedSubject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $message .= "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $message .= $plainText . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $htmlBody . "\r\n";
        $message .= "--{$boundary}--";

        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) {
            error_log("[KIM Mailer] FAILED to connect: {$errstr} (errno {$errno})");
            return false;
        }
        error_log("[KIM Mailer] Connected.");

        $code = self::smtpRead($socket);
        if ($code !== '220') {
            error_log("[KIM Mailer] Bad greeting (expected 220, got {$code})");
            fclose($socket); return false;
        }

        self::smtpWrite($socket, "EHLO kim-fitness.ro\r\n");
        $code = self::smtpRead($socket);
        if ($code !== '250') {
            error_log("[KIM Mailer] EHLO failed (got {$code})");
            fclose($socket); return false;
        }

        self::smtpWrite($socket, "AUTH LOGIN\r\n");
        $code = self::smtpRead($socket);
        if ($code !== '334') {
            error_log("[KIM Mailer] AUTH LOGIN failed (got {$code})");
            fclose($socket); return false;
        }

        self::smtpWrite($socket, $userB64 . "\r\n", '[USERNAME base64]');
        $code = self::smtpRead($socket);
        if ($code !== '334') {
            error_log("[KIM Mailer] Username rejected (got {$code})");
            fclose($socket); return false;
        }

        self::smtpWrite($socket, $passB64 . "\r\n", '[PASSWORD base64]');
        $code = self::smtpRead($socket);
        if ($code !== '235') {
            error_log("[KIM Mailer] Authentication failed (got {$code}) — check credentials");
            fclose($socket); return false;
        }
        error_log("[KIM Mailer] Authenticated successfully.");

        self::smtpWrite($socket, "MAIL FROM:<{$from}>\r\n");
        $code = self::smtpRead($socket);
        if ($code !== '250') {
            error_log("[KIM Mailer] MAIL FROM failed (got {$code})");
            fclose($socket); return false;
        }

        self::smtpWrite($socket, "RCPT TO:<{$to}>\r\n");
        $code = self::smtpRead($socket);
        if ($code !== '250') {
            error_log("[KIM Mailer] RCPT TO failed (got {$code})");
            fclose($socket); return false;
        }

        self::smtpWrite($socket, "DATA\r\n");
        $code = self::smtpRead($socket);
        if ($code !== '354') {
            error_log("[KIM Mailer] DATA failed (got {$code})");
            fclose($socket); return false;
        }

        fwrite($socket, $message . "\r\n.\r\n");
        $code = self::smtpRead($socket);

        self::smtpWrite($socket, "QUIT\r\n");
        fclose($socket);

        if ($code === '250') {
            error_log("[KIM Mailer] ✅ Email sent successfully to {$to} | Subject: {$subject}");
            return true;
        }

        error_log("[KIM Mailer] Message rejected after DATA (got {$code})");
        return false;
    }

    // Incadreaza continutul emailului intr-un template HTML comun (cu header si footer KIM)
    private static function wrapTemplate($recipientName, $content) {
        return <<<HTML
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KIM Fitness</title>
</head>
<body style="margin:0;padding:0;background-color:#f9fafb;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="100%" style="max-width:560px;background:#ffffff;border-radius:4px;overflow:hidden;border:1px solid #e5e7eb;">
          <tr>
            <td style="background:#111827;padding:24px 32px;">
              <h1 style="margin:0;color:#ffffff;font-size:1.4rem;font-weight:700;letter-spacing:-0.5px;">KIM Fitness</h1>
              <p style="margin:4px 0 0;color:#9ca3af;font-size:0.82rem;">Management Platform</p>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px 0;">
              <p style="margin:0;color:#374151;font-size:0.95rem;">Buna ziua, <strong>{$recipientName}</strong>,</p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px 28px;">
              {$content}
            </td>
          </tr>
          <tr>
            <td style="background:#f9fafb;padding:18px 32px;border-top:1px solid #e5e7eb;">
              <p style="margin:0;color:#9ca3af;font-size:0.78rem;">Acest email a fost generat automat de platforma KIM Fitness. Te rugam sa nu raspunzi la acest mesaj.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    // Genereaza corpul emailului pentru confirmarea unei rezervari la o sedinta de grup
    public static function bookingConfirmationBody($session) {
        $title    = htmlspecialchars($session['title']);
        $category = ucfirst($session['category'] ?? '');
        $trainer  = htmlspecialchars($session['trainer_name'] ?? '');
        $room     = htmlspecialchars($session['room_name'] ?? '—');
        $start    = date('d.m.Y H:i', strtotime($session['start_time']));
        $end      = date('H:i',       strtotime($session['end_time']));

        return <<<HTML
<p style="color:#374151;font-size:0.95rem;line-height:1.6;">
  Rezervarea ta a fost inregistrata cu succes! Te asteptam la sesiunea:
</p>
<div style="background:#f0fdf4;border-left:4px solid #10b981;border-radius:4px;padding:16px 20px;margin:16px 0;">
  <p style="margin:0 0 6px;font-size:1.05rem;font-weight:700;color:#111827;">{$title}</p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">📅 {$start} – {$end}</p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">👤 Antrenor: {$trainer}</p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">📍 Sala: {$room}</p>
  <p style="margin:0;color:#374151;font-size:0.88rem;">🏷️ Categorie: {$category}</p>
</div>
<p style="color:#6b7280;font-size:0.85rem;line-height:1.5;">
  Daca nu mai poti participa, te rugam sa anulezi inscrierea din aplicatie cu cel putin 2 ore inainte.
</p>
HTML;
    }

    // Genereaza corpul emailului cand o sedinta programata a fost anulata de admin sau antrenor
    public static function sessionCancelledBody($session, $reason = '') {
        $title  = htmlspecialchars($session['title']);
        $start  = date('d.m.Y H:i', strtotime($session['start_time']));
        $end    = date('H:i',       strtotime($session['end_time']));
        $reasonHtml = $reason
            ? '<p style="margin:12px 0 0;color:#6b7280;font-size:0.85rem;"><strong>Motiv:</strong> ' . htmlspecialchars($reason) . '</p>'
            : '';

        return <<<HTML
<p style="color:#374151;font-size:0.95rem;line-height:1.6;">
  Iti comunicam ca sesiunea la care esti inscris a fost <strong style="color:#dc2626;">anulata</strong>.
</p>
<div style="background:#fef2f2;border-left:4px solid #dc2626;border-radius:4px;padding:16px 20px;margin:16px 0;">
  <p style="margin:0 0 6px;font-size:1.05rem;font-weight:700;color:#111827;">{$title}</p>
  <p style="margin:0;color:#374151;font-size:0.88rem;">📅 {$start} – {$end}</p>
  {$reasonHtml}
</div>
<p style="color:#6b7280;font-size:0.85rem;line-height:1.5;">
  Ne cerem scuze pentru inconvenient. Poti verifica orarul actualizat in platforma si te poti inscrie la o alta sesiune disponibila.
</p>
HTML;
    }

    // Genereaza corpul emailului pentru acceptarea unei cereri de sedinta privata
    public static function privateRequestAcceptedBody($req, $trainerName, $roomName) {
        $title    = htmlspecialchars($req['title']);
        $category = ucfirst($req['category'] ?? '');
        $trainer  = htmlspecialchars($trainerName);
        $room     = htmlspecialchars($roomName ?? '—');
        $date     = date('d.m.Y', strtotime($req['date']));
        $start    = date('H:i',     strtotime($req['start_time']));
        $end      = date('H:i',     strtotime($req['end_time']));

        return <<<HTML
<p style="color:#374151;font-size:0.95rem;line-height:1.6;">
  Cererea ta pentru o <strong>sesiune privata</strong> a fost aprobata cu succes! Detaliile programarii tale sunt:
</p>
<div style="background:#f0fdf4;border-left:4px solid #10b981;border-radius:4px;padding:16px 20px;margin:16px 0;">
  <p style="margin:0 0 6px;font-size:1.05rem;font-weight:700;color:#111827;">{$title} (Privat)</p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">📅 Data: <strong>{$date}</strong></p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">⏰ Interval orar: <strong>{$start} – {$end}</strong></p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">👤 Antrenor personal: <strong>{$trainer}</strong></p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">📍 Sala: <strong>{$room}</strong></p>
  <p style="margin:0;color:#374151;font-size:0.88rem;">🏷️ Categorie: <strong>{$category}</strong></p>
</div>
<p style="color:#374151;font-size:0.95rem;line-height:1.6;">
  Sesiunea a fost adaugata in calendarul tau privat. Ne vedem la antrenament!
</p>
HTML;
    }

    // Genereaza corpul emailului pentru respingerea unei cereri de sedinta privata
    public static function privateRequestDeniedBody($req, $handlerName, $handlerRole) {
        $title    = htmlspecialchars($req['title']);
        $date     = date('d.m.Y', strtotime($req['date']));
        $start    = date('H:i',     strtotime($req['start_time']));
        $end      = date('H:i',     strtotime($req['end_time']));
        $handler  = htmlspecialchars($handlerName);
        $roleName = $handlerRole === 'admin' ? 'Administrator' : ($handlerRole === 'trainer' ? 'Antrenor' : 'Kinetoterapeut');

        return <<<HTML
<p style="color:#374151;font-size:0.95rem;line-height:1.6;">
  Iti comunicam ca cererea ta pentru sesiunea privata a fost <strong style="color:#dc2626;">respinsa</strong>.
</p>
<div style="background:#fef2f2;border-left:4px solid #dc2626;border-radius:4px;padding:16px 20px;margin:16px 0;">
  <p style="margin:0 0 6px;font-size:1.05rem;font-weight:700;color:#111827;">{$title}</p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">📅 Data programata: {$date}</p>
  <p style="margin:0 0 4px;color:#374151;font-size:0.88rem;">⏰ Interval orar: {$start} – {$end}</p>
  <p style="margin:0;color:#374151;font-size:0.88rem;">👤 Solutionata de: {$handler} ({$roleName})</p>
</div>
<p style="color:#6b7280;font-size:0.85rem;line-height:1.5;">
  Ne cerem scuze pentru neplaceri. Te invitam sa trimiti o noua cerere alegand un alt interval orar sau un alt specialist din echipa.
</p>
HTML;
    }
}

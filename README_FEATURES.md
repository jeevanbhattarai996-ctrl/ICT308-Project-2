# CarePlus HMS — Feature Upgrade

This build adds the 10 "HD" features on top of the original CarePlus Healthcare
Management System (PHP + MySQL, mysqli, session-based auth).

## Setup

1. Import the database in order:
   ```
   mysql -u root -p < database/careplus_hms.sql
   mysql -u root -p < database/upgrade_features.sql
   ```
2. Point your web server / PHP built-in server at the project root, e.g.:
   ```
   php -S localhost:8000
   ```
   and open `http://localhost:8000/login.php`.
3. Make sure `uploads/health_records/` is writable by the web server (used by
   the Electronic Health Records upload feature).
4. Sample logins (see `database/careplus_hms.sql`):
   - Admin: `admin@careplus.com`
   - Manager: `manager@careplus.com`
   - Doctors: `dr.smith@careplus.com`, `dr.jones@careplus.com`
   - Patients: `patient1@example.com`, `patient2@example.com`
   (Passwords are whatever was set when the sample hashes were generated —
   replace with your own test accounts if unsure, via `register.php`.)

## New features and how they really work

| Feature | Where | Notes |
|---|---|---|
| AI Symptom Checker | `patient/symptom_checker.php` + `includes/symptom_engine.php` | Rule-based symptom-overlap scoring, not a trained model. Flags emergency symptoms (chest pain, difficulty breathing, severe bleeding, loss of consciousness, slurred speech) with a warning banner regardless of score. |
| AI Chat Assistant | Floating widget on every page (`assets/js/chatbot.js`, `includes/chatbot_handler.php`) | Keyword-matching FAQ bot (hours, doctors, booking, symptoms, medications, emergencies). |
| Video Consultation | `patient/book_appointment.php` (choose "Video"), `video_room.php`, `video_signal.php` | **Real WebRTC** peer-to-peer video/audio using the browser's camera and mic. Signalling (the SDP offer/answer + ICE candate handshake) goes through a simple database-polling endpoint instead of a websocket server, so no extra infrastructure is needed. To test locally, open the same appointment as both the patient and the doctor (e.g. two browser profiles) and click "Join Call" on each. |
| Health Dashboard | `patient/health_dashboard.php` | Patients log BP, heart rate, blood sugar, weight/height (BMI derived), and daily steps; Chart.js line/bar charts show trends. |
| Disease / Risk Prediction | `patient/risk_score.php` | Transparent point-based scoring for diabetes / heart / hypertension risk from vitals + age + BMI + free-text family history. Not a diagnostic tool — the page says so. |
| Smart Medicine Reminder | `patient/medications.php`, `cron/medication_reminder.php` | In-app schedule + "mark as taken" tracking works out of the box. Email reminders use PHP's `mail()` — needs a configured mail server. SMS/push are stubbed with clear notes on what a real integration (Twilio / Web Push) would require, since those need paid credentials this environment can't provide. |
| QR Medical Card | `patient/qr_card.php`, `medical_card_view.php` | Generates a real scannable QR code (client-side, via qrcode.js) linking to a public, token-secured emergency page showing blood group, allergies, and emergency contact — no login required, like a real emergency card. |
| Electronic Health Records | `patient/health_records.php`, `download_record.php` | Upload/download lab reports, prescriptions, X-rays (PDF/JPG/PNG, 8MB max), plus vaccination and allergy tracking. Downloads are access-controlled (only the owning patient, a treating doctor, or an admin can fetch a file) and the upload folder blocks script execution. |
| Doctor Analytics | `doctor/analytics.php` | Appointments trend, status breakdown, estimated monthly income (completed visits × consultation fee), patient satisfaction (from post-visit ratings), common diagnoses — all Chart.js. |
| Admin Analytics | `admin/analytics.php` | New patients/month, estimated revenue/month, doctor performance, 14-day appointment trend, hospital-wide diagnosis stats. Revenue is explicitly labelled as an estimate, not real billing data. |
| Emergency SOS | `patient/emergency_sos.php`, `admin/sos_alerts.php` | One click logs an alert (with GPS location if the browser allows it), notifies all admins in-app, and shows the patient's emergency contact. Admins can view/resolve alerts and open the shared location in Google Maps. |

## Deliberately left out / simplified

A few items from the original wishlist need infrastructure this project
can't include (paid APIs, hardware, or certification), so they were either
scoped down to something real, or left out rather than faked:

- **Face recognition login** — needs a biometric SDK/model and camera
  permission flow beyond a student project's scope; not implemented.
- **Wearable integration (Apple Watch/Fitbit/Galaxy Watch)** — requires each
  vendor's paid developer program and OAuth app approval; not implemented.
  The Health Dashboard is built so this data *could* feed into the same
  `health_vitals` table later.
- **Live ambulance GPS tracking** — needs a fleet-tracking backend and real
  vehicles; not implemented.
- **Real payments (Visa/Mastercard/Apple Pay/PayPal/Google Pay)** — needs a
  merchant account and PCI-compliant handling; not implemented. The
  `consultation_fee` field on doctors exists so a real payment step could be
  added later without a schema change.
- **Multi-language support** — not implemented; would be a text-extraction +
  i18n pass across all pages.
- **PWA install** — not implemented; would need a manifest + service worker.

Everything listed as "implemented" above is real, working code against the
actual database — nothing is a static mockup.

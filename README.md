# Asahi Running Club - Attendance Management Plugin

A WordPress plugin for managing event attendance, RSVPs, and response lists.

[日本語版はこちら](README.ja.md)

---

## Features

- ✅ Create / edit / delete events (admin panel)
- ✅ Customizable response choices per event
- ✅ Ajax-based response registration and editing
- ✅ Auto-close form after deadline
- ✅ Response summary with counts per choice
- ✅ Sortable response table (click column headers)
- ✅ Mobile-friendly layout
- ✅ Delete individual responses from admin panel

---

## Installation

1. Upload the `asahi-attendance` folder to `/wp-content/plugins/`.
2. Activate the plugin from **WordPress Admin > Plugins**.
3. Database tables are created automatically on activation.

---

## Usage

### 1. Create an Event

Go to **Admin > Attendance > New Event** and fill in:

| Field | Description |
|-------|-------------|
| Title | e.g. "Enoshima Maranik 6/7" |
| Event Date | Date and time of the event |
| Description | Message for participants |
| Deadline | Form auto-closes after this date/time |
| Choices | One choice per line, e.g. `Attend / Absent / Undecided` |

### 2. Embed the Shortcode

Paste the shortcode into any post or page:

\`\`\`
[aap_event id="1"]
\`\`\`

The `id` is shown in the event list.

### 3. Participants Respond

- Enter name, select a choice, and optionally add a comment.
- After submitting, an edit link is provided.
- Responses can be edited anytime via `?aap_edit=<token>` URL.

---

## Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+

---

## License

GPL v2 or later

// ─────────────────────────────────────────────────────────────────
// /boek — public booking page behaviour.
//
// Bundled by Vite (entry: `boek`) and enqueued only on the /boek route.
// Replaces the old inline <script> + CDN intl-tel-input. Server-side values
// (REST url, nonce, language, strings) arrive via wp_localize_script as
// `window.mlBoek` — see ml_enqueue_boek_assets() in functions.php.
// ─────────────────────────────────────────────────────────────────

import intlTelInput from 'intl-tel-input'
import 'intl-tel-input/build/css/intlTelInput.css'
import utilsURL from 'intl-tel-input/build/js/utils.js?url'
import '../scss/boek.scss'

const cfg = window.mlBoek || {}
const t = cfg.i18n || {}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('ml-boek-form')
    if (!form) return

    const submit = document.getElementById('ml-boek-submit')
    const errBox = document.getElementById('ml-boek-error')
    const ctaTxt = submit ? submit.textContent.trim() : ''

    // ── International phone input (flags + dial codes) ──
    const phoneEl = document.getElementById('ml-boek-phone')
    const iti = intlTelInput(phoneEl, {
        initialCountry: 'be',
        preferredCountries: ['be', 'nl', 'de', 'fr', 'lu', 'gb'],
        separateDialCode: true,
        utilsScript: utilsURL,
    })

    // ── Address autocomplete (OpenStreetMap / Nominatim) ──
    const streetEl  = document.getElementById('ml-boek-street')
    const acBox     = document.getElementById('ml-boek-ac')
    const postEl    = document.getElementById('ml-boek-postcode')
    const cityEl    = document.getElementById('ml-boek-city')
    const stateEl   = document.getElementById('ml-boek-state')
    const countryEl = document.getElementById('ml-boek-country')
    const lang      = cfg.lang || 'nl'
    let acTimer = null
    let acCtrl  = null

    function closeAc() {
        acBox.classList.remove('is-open')
        acBox.innerHTML = ''
    }

    function pick(item) {
        const a = item.address || {}
        const road = a.road || a.pedestrian || a.cycleway || a.footway || a.path || ''
        const num  = a.house_number ? ` ${a.house_number}` : ''
        if (road) streetEl.value = road + num
        if (a.postcode) postEl.value = a.postcode
        const city = a.city || a.town || a.village || a.municipality || a.hamlet || a.county || ''
        if (city) cityEl.value = city
        if (a.state || a.region || a.province) stateEl.value = a.state || a.region || a.province
        if (a.country_code) {
            const cc = a.country_code.toUpperCase()
            if (countryEl.querySelector(`option[value="${cc}"]`)) countryEl.value = cc
        }
        closeAc()
    }

    function runSearch(q) {
        if (acCtrl) acCtrl.abort()
        acCtrl = new AbortController()
        const url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=6'
            + `&accept-language=${encodeURIComponent(lang)}`
            + `&q=${encodeURIComponent(q)}`
        fetch(url, { signal: acCtrl.signal, headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(rows => {
                acBox.innerHTML = ''
                if (!rows || !rows.length) { closeAc(); return }
                rows.forEach(row => {
                    const el = document.createElement('div')
                    el.className = 'boek-ac__item'
                    el.textContent = row.display_name
                    el.addEventListener('mousedown', e => { e.preventDefault(); pick(row) })
                    acBox.appendChild(el)
                })
                acBox.classList.add('is-open')
            })
            .catch(() => { /* aborted or network — ignore */ })
    }

    streetEl.addEventListener('input', () => {
        const q = streetEl.value.trim()
        if (acTimer) clearTimeout(acTimer)
        if (q.length < 3) { closeAc(); return }
        acTimer = setTimeout(() => runSearch(q), 350)
    })
    streetEl.addEventListener('blur', () => setTimeout(closeAc, 150))

    // ── Submit ──
    form.addEventListener('submit', e => {
        e.preventDefault()
        errBox.style.display = 'none'

        const payload = {}
        new FormData(form).forEach((v, k) => { payload[k] = v })
        payload.phone = iti.getNumber() || payload.phone // E.164 if valid

        if (!payload.date || !payload.time) {
            errBox.textContent = t.pickSlot || 'Kies eerst een datum en uur.'
            errBox.style.display = 'block'
            return
        }

        submit.disabled = true
        submit.textContent = t.loading || 'Laden...'
        fetch(cfg.restUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(out => {
                if (out && out.ok && out.url) { window.location = out.url; return }
                errBox.textContent = (out && out.error) || t.errorGeneric || 'Er ging iets mis.'
                errBox.style.display = 'block'
                submit.disabled = false
                submit.textContent = ctaTxt
            })
            .catch(() => {
                errBox.textContent = t.network || 'Network error.'
                errBox.style.display = 'block'
                submit.disabled = false
                submit.textContent = ctaTxt
            })
    })
})

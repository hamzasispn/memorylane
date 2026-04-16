import Alpine from 'alpinejs'
import '../scss/main.scss'
import { animate, inView, stagger } from 'motion'

window.Alpine = Alpine
Alpine.start()

// ─── Hero Section ──────────────────────────────────────────────
const heroBtn = document.querySelector('.h-screen .btn-primary')
if (heroBtn) {
    animate(
        heroBtn,
        { opacity: [0, 1], y: [40, 0] },
        { duration: 0.9, delay: 0.6, easing: [0.25, 0.46, 0.45, 0.94] }
    )
}

// ─── About Section ─────────────────────────────────────────────
inView('.about-us', () => {
    const textCol = document.querySelector('.about-us .grid > div:first-child')
    const imgCol  = document.querySelector('.about-us .grid > div:last-child')
    const signPole = document.querySelector('.about-us > img')

    if (textCol) {
        animate(textCol, { opacity: [0, 1], x: [-60, 0] }, { duration: 0.85, easing: [0.25, 0.46, 0.45, 0.94] })
    }
    if (imgCol) {
        animate(imgCol, { opacity: [0, 1], x: [60, 0] }, { duration: 0.85, delay: 0.15, easing: [0.25, 0.46, 0.45, 0.94] })
    }
    if (signPole) {
        animate(signPole, { opacity: [0, 1], y: [-40, 0] }, { duration: 1, delay: 0.3, easing: [0.25, 0.46, 0.45, 0.94] })
    }
}, { amount: 0.2 })

// ─── Why Choose Section ─────────────────────────────────────────
inView('.why-choose-us', () => {
    const header = document.querySelector('.why-choose-us .section-header')
    const cards  = document.querySelectorAll('.why-choose-us .grid > *')

    if (header) {
        animate(header, { opacity: [0, 1], y: [40, 0] }, { duration: 0.7, easing: 'ease-out' })
    }
    if (cards.length) {
        animate(cards, { opacity: [0, 1], y: [50, 0] }, {
            duration: 0.7,
            delay: stagger(0.15, { start: 0.2 }),
            easing: [0.25, 0.46, 0.45, 0.94]
        })
    }
}, { amount: 0.15 })

// ─── How It Works Section ───────────────────────────────────────
inView('.how-its-work', () => {
    const header = document.querySelector('.how-its-work .section-header')
    const steps  = document.querySelectorAll('.how-its-work .row-gap-20 > .how-its-work-card')
    const arrow  = document.querySelector('.how-its-work img[src*="Aerrow"]')
    const btn    = document.querySelector('.how-its-work .btn-primary')

    if (header) {
        animate(header, { opacity: [0, 1], y: [40, 0] }, { duration: 0.7, easing: 'ease-out' })
    }
    if (steps.length) {
        animate(steps, { opacity: [0, 1], x: [-50, 0] }, {
            duration: 0.7,
            delay: stagger(0.2, { start: 0.2 }),
            easing: [0.25, 0.46, 0.45, 0.94]
        })
    }
    if (arrow) {
        animate(arrow, { opacity: [0, 1], scale: [0.8, 1] }, { duration: 0.8, delay: 0.5, easing: 'ease-out' })
    }
    if (btn) {
        animate(btn, { opacity: [0, 1], y: [20, 0] }, { duration: 0.6, delay: 0.6, easing: 'ease-out' })
    }
}, { amount: 0.15 })

// ─── Video Section ──────────────────────────────────────────────
inView('.video-section', () => {
    const header = document.querySelector('.video-section .section-header')
    const iframe = document.querySelector('.video-section iframe')

    if (header) {
        animate(header, { opacity: [0, 1], y: [40, 0] }, { duration: 0.7, easing: 'ease-out' })
    }
    if (iframe) {
        animate(iframe, { opacity: [0, 1], scale: [0.95, 1] }, { duration: 0.85, delay: 0.2, easing: [0.25, 0.46, 0.45, 0.94] })
    }
}, { amount: 0.15 })

// ─── Packages Section ───────────────────────────────────────────
inView('.package-section', () => {
    const header  = document.querySelector('.package-section .section-header')
    const cards   = document.querySelectorAll('.package-section .grid > *')
    const banner  = document.querySelector('.package-section .rounded-\\[2\\.604vw\\]')

    if (header) {
        animate(header, { opacity: [0, 1], y: [40, 0] }, { duration: 0.7, easing: 'ease-out' })
    }
    if (cards.length) {
        animate(cards, { opacity: [0, 1], y: [60, 0] }, {
            duration: 0.75,
            delay: stagger(0.18, { start: 0.2 }),
            easing: [0.25, 0.46, 0.45, 0.94]
        })
    }
    if (banner) {
        animate(banner, { opacity: [0, 1], y: [30, 0] }, { duration: 0.7, delay: 0.7, easing: 'ease-out' })
    }
}, { amount: 0.1 })

// ─── Contact Section ────────────────────────────────────────────
inView('.contact-us', () => {
    const header      = document.querySelector('.contact-us .section-header')
    const bookBtn     = document.querySelector('.contact-us .btn-primary')
    const contactList = document.querySelector('.contact-us .flex.justify-center')
    const form        = document.querySelector('.contact-us .bg-white\\/60')

    if (header) {
        animate(header, { opacity: [0, 1], y: [40, 0] }, { duration: 0.7, easing: 'ease-out' })
    }
    if (bookBtn) {
        animate(bookBtn, { opacity: [0, 1], y: [20, 0] }, { duration: 0.6, delay: 0.2, easing: 'ease-out' })
    }
    if (contactList) {
        animate(contactList, { opacity: [0, 1], x: [-50, 0] }, { duration: 0.8, delay: 0.3, easing: [0.25, 0.46, 0.45, 0.94] })
    }
    if (form) {
        animate(form, { opacity: [0, 1], x: [50, 0] }, { duration: 0.8, delay: 0.3, easing: [0.25, 0.46, 0.45, 0.94] })
    }
}, { amount: 0.1 })

// ─── Hover Effects ──────────────────────────────────────────────
function addHoverEffect(selector, inProps, outProps, options = {}) {
    document.querySelectorAll(selector).forEach(el => {
        el.addEventListener('mouseenter', () => animate(el, inProps, { duration: 0.25, easing: 'ease-out', ...options }))
        el.addEventListener('mouseleave', () => animate(el, outProps, { duration: 0.25, easing: 'ease-out', ...options }))
    })
}

// Cards — why choose + packages
addHoverEffect('.why-choose-us .grid > *', { y: -8, scale: 1.02 }, { y: 0, scale: 1 })
addHoverEffect('.package-section .grid > *', { y: -10, scale: 1.02 }, { y: 0, scale: 1 })

// How-it-works step cards
addHoverEffect('.how-its-work-card', { x: 6 }, { x: 0 })

// Buttons
addHoverEffect('.btn-primary', { scale: 1.05 }, { scale: 1 })
addHoverEffect('.btn-secondary', { scale: 1.05 }, { scale: 1 })

// Contact list icons
document.querySelectorAll('.contact-us li img').forEach(img => {
    img.addEventListener('mouseenter', () => animate(img, { rotate: 15, scale: 1.15 }, { duration: 0.2 }))
    img.addEventListener('mouseleave', () => animate(img, { rotate: 0, scale: 1 }, { duration: 0.2 }))
})

// Video section — subtle pulse on load
inView('.video-section iframe', (el) => {
    animate(el, { boxShadow: ['0 0 0px rgba(138,43,226,0)', '0 0 40px rgba(138,43,226,0.25)', '0 0 0px rgba(138,43,226,0)'] }, {
        duration: 2,
        delay: 1,
        easing: 'ease-in-out'
    })
}, { amount: 0.5 })

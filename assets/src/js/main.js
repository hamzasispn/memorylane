import Alpine from 'alpinejs'
import '../scss/main.scss'
import { animate, inView, stagger } from 'motion'

window.Alpine = Alpine
Alpine.start()

// ─────────────────────────────────────────────────────────────────
// TEXT ANIMATION HELPERS
// ─────────────────────────────────────────────────────────────────

/**
 * Splits element text into individual character <span>s for typewriter effect.
 * Sets each char to opacity 0. The element itself becomes opacity 1.
 */
function initTypewriter(el) {
    const text = el.textContent.trim()
    if (!text) return
    el.setAttribute('data-tw-ready', '1')
    el.style.opacity = '1'
    el.innerHTML = text
        .split('')
        .map(c => {
            if (c === ' ') return '<span class="tw-c" style="opacity:0;display:inline"> </span>'
            return `<span class="tw-c" style="opacity:0">${c}</span>`
        })
        .join('')
}

/**
 * Plays the typewriter animation on a prepared element.
 */
function playTypewriter(el) {
    const chars = Array.from(el.querySelectorAll('.tw-c'))
    if (!chars.length) return
    animate(chars, { opacity: 1 }, {
        delay: stagger(0.042),
        duration: 0.001,
        easing: 'linear',
    })
}

/**
 * Splits element text into individual word <span>s for fade-in effect.
 * Sets each word to opacity 0. The element itself becomes opacity 1.
 */
function initWordFade(el) {
    const text = el.textContent.trim()
    if (!text) return
    el.setAttribute('data-wf-ready', '1')
    el.style.opacity = '1'
    el.innerHTML = text
        .split(/\s+/)
        .map(w => `<span class="wd-c" style="opacity:0;display:inline-block;margin-right:0.28em;white-space:nowrap">${w}</span>`)
        .join('')
}

/**
 * Plays the word-fade animation on a prepared element.
 */
function playWordFade(el) {
    const words = Array.from(el.querySelectorAll('.wd-c'))
    if (!words.length) return
    animate(words, { opacity: [0, 1], y: [6, 0] }, {
        duration: 0.38,
        delay: stagger(0.028, { start: 0.05 }),
        easing: 'ease-out',
    })
}

// ─────────────────────────────────────────────────────────────────
// INITIALISE ON DOM READY
// ─────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {

    // ── Sticky header ──────────────────────────────────────────────
    const siteHeader = document.querySelector('.site-header')
    if (siteHeader) {
        const syncHeader = () => siteHeader.classList.toggle('scrolled', window.scrollY > 50)
        window.addEventListener('scroll', syncHeader, { passive: true })
        syncHeader()
    }

    // ── Scroll-driven steps (hoe-werkt page) ───────────────────────
    const stepsOuter = document.querySelector('.steps-scroll-outer')
    if (stepsOuter) {
        const steps = Array.from(stepsOuter.querySelectorAll('.scroll-step'))
        if (steps.length) {
            steps.forEach(s => { s.style.opacity = '0'; s.style.transform = 'translateY(44px)' })

            if (window.innerWidth < 768) {
                // Mobile: simple inView stagger
                steps.forEach(s => {
                    inView(s, () => animate(s, { opacity: 1, y: [44, 0] }, {
                        duration: 0.6, easing: [0.25, 0.46, 0.45, 0.94],
                    }), { amount: 0.3 })
                })
            } else {
                // Desktop: pinned one-by-one reveal
                const scrollPerStep = Math.max(window.innerHeight * 0.72, 420)
                stepsOuter.style.minHeight = (window.innerHeight + steps.length * scrollPerStep) + 'px'

                function updateSteps() {
                    const scrolled = Math.max(0, -stepsOuter.getBoundingClientRect().top)
                    steps.forEach((step, i) => {
                        const wasVis = step.dataset.vis === '1'
                        const isVis  = scrolled >= i * scrollPerStep
                        if (isVis === wasVis) return
                        step.dataset.vis = isVis ? '1' : '0'
                        isVis
                            ? animate(step, { opacity: 1, y: [44, 0] }, { duration: 0.55, easing: [0.25, 0.46, 0.45, 0.94] })
                            : animate(step, { opacity: 0, y: 44 },      { duration: 0.32, easing: 'ease-in' })
                    })
                }
                window.addEventListener('scroll', updateSteps, { passive: true })
                window.addEventListener('resize', () => {
                    stepsOuter.style.minHeight = (window.innerHeight + steps.length * Math.max(window.innerHeight * 0.72, 420)) + 'px'
                    updateSteps()
                })
                updateSteps()
            }
        }
    }

    // ── Typewriter: section headers + page headings ────────────────
    document.querySelectorAll(
        '.section-header h2, .page-hero-title, .page-section-title'
    ).forEach(el => {
        initTypewriter(el)
        inView(el, () => playTypewriter(el), { amount: 0.6 })
    })

    // ── Word-fade: descriptions & body text ────────────────────────
    document.querySelectorAll(
        '.section-header p, .page-description, .animate-words'
    ).forEach(el => {
        initWordFade(el)
        inView(el, () => playWordFade(el), { amount: 0.35 })
    })

    // ── Generic fade-up elements (.animate-fade-up) ────────────────
    document.querySelectorAll('.animate-fade-up').forEach(el => {
        el.style.opacity = '0'
        inView(el, () => {
            animate(el, { opacity: [0, 1], y: [28, 0] }, {
                duration: 0.6,
                easing: [0.25, 0.46, 0.45, 0.94],
            })
        }, { amount: 0.25 })
    })

    // ── Stagger card grids (.animate-stagger-parent) ───────────────
    document.querySelectorAll('.animate-stagger-parent').forEach(parent => {
        const cards = Array.from(parent.querySelectorAll('.animate-stagger-child'))
        cards.forEach(c => { c.style.opacity = '0' })
        inView(parent, () => {
            animate(cards, { opacity: [0, 1], y: [40, 0] }, {
                duration: 0.6,
                delay: stagger(0.12, { start: 0.08 }),
                easing: [0.25, 0.46, 0.45, 0.94],
            })
        }, { amount: 0.12 })
    })

    // ── Section-header container: slide up (opacity handled by chars) ──
    document.querySelectorAll('.section-header').forEach(el => {
        inView(el, () => {
            animate(el, { y: [32, 0] }, { duration: 0.65, easing: [0.25, 0.46, 0.45, 0.94] })
        }, { amount: 0.3 })
    })

})

// ─────────────────────────────────────────────────────────────────
// FRONT-PAGE SECTION ANIMATIONS
// ─────────────────────────────────────────────────────────────────

// ── Hero button ────────────────────────────────────────────────
const heroBtn = document.querySelector('.h-screen .btn-primary')
if (heroBtn) {
    animate(
        heroBtn,
        { opacity: [0, 1], y: [40, 0] },
        { duration: 0.9, delay: 0.6, easing: [0.25, 0.46, 0.45, 0.94] }
    )
}

// ── About section ──────────────────────────────────────────────
inView('.about-us', () => {
    const textCol  = document.querySelector('.about-us .grid > div:first-child')
    const imgCol   = document.querySelector('.about-us .grid > div:last-child')
    const signPole = document.querySelector('.about-us > img')

    if (textCol)  animate(textCol,  { opacity: [0, 1], x: [-60, 0] }, { duration: 0.85, easing: [0.25, 0.46, 0.45, 0.94] })
    if (imgCol)   animate(imgCol,   { opacity: [0, 1], x: [ 60, 0] }, { duration: 0.85, delay: 0.15, easing: [0.25, 0.46, 0.45, 0.94] })
    if (signPole) animate(signPole, { opacity: [0, 1], y: [-40, 0] }, { duration: 1,    delay: 0.3,  easing: [0.25, 0.46, 0.45, 0.94] })
}, { amount: 0.2 })

// ── Why-choose cards ───────────────────────────────────────────
inView('.why-choose-us', () => {
    const cards = Array.from(document.querySelectorAll('.why-choose-us .grid > *'))
    if (cards.length) {
        animate(cards, { opacity: [0, 1], y: [50, 0] }, {
            duration: 0.7,
            delay: stagger(0.15, { start: 0.2 }),
            easing: [0.25, 0.46, 0.45, 0.94],
        })
    }
}, { amount: 0.15 })

// ── How-it-works steps ─────────────────────────────────────────
inView('.how-its-work', () => {
    const steps = Array.from(document.querySelectorAll('.how-its-work .row-gap-20 > .how-its-work-card'))
    const arrow = document.querySelector('.how-its-work img[src*="Aerrow"]')
    const btn   = document.querySelector('.how-its-work .btn-primary')

    if (steps.length) {
        animate(steps, { opacity: [0, 1], x: [-50, 0] }, {
            duration: 0.7,
            delay: stagger(0.2, { start: 0.2 }),
            easing: [0.25, 0.46, 0.45, 0.94],
        })
    }
    if (arrow) animate(arrow, { opacity: [0, 1], scale: [0.8, 1] }, { duration: 0.8, delay: 0.5, easing: 'ease-out' })
    if (btn)   animate(btn,   { opacity: [0, 1], y: [20, 0] },      { duration: 0.6, delay: 0.6, easing: 'ease-out' })
}, { amount: 0.15 })

// ── Video section ──────────────────────────────────────────────
inView('.video-section', () => {
    const iframe = document.querySelector('.video-section iframe')
    if (iframe) animate(iframe, { opacity: [0, 1], scale: [0.95, 1] }, { duration: 0.85, delay: 0.2, easing: [0.25, 0.46, 0.45, 0.94] })
}, { amount: 0.15 })

// ── Packages cards ─────────────────────────────────────────────
inView('.package-section', () => {
    const cards  = Array.from(document.querySelectorAll('.package-section .grid > *'))
    const banner = document.querySelector('.package-section .rounded-\\[2\\.604vw\\]')

    if (cards.length) {
        animate(cards, { opacity: [0, 1], y: [60, 0] }, {
            duration: 0.75,
            delay: stagger(0.18, { start: 0.2 }),
            easing: [0.25, 0.46, 0.45, 0.94],
        })
    }
    if (banner) animate(banner, { opacity: [0, 1], y: [30, 0] }, { duration: 0.7, delay: 0.7, easing: 'ease-out' })
}, { amount: 0.1 })

// ── Contact section ────────────────────────────────────────────
inView('.contact-us', () => {
    const bookBtn     = document.querySelector('.contact-us .btn-primary')
    const contactList = document.querySelector('.contact-us .flex.justify-center')
    const form        = document.querySelector('.contact-us .bg-white\\/60')

    if (bookBtn)     animate(bookBtn,     { opacity: [0, 1], y: [20, 0]  }, { duration: 0.6, delay: 0.2, easing: 'ease-out' })
    if (contactList) animate(contactList, { opacity: [0, 1], x: [-50, 0] }, { duration: 0.8, delay: 0.3, easing: [0.25, 0.46, 0.45, 0.94] })
    if (form)        animate(form,        { opacity: [0, 1], x: [ 50, 0] }, { duration: 0.8, delay: 0.3, easing: [0.25, 0.46, 0.45, 0.94] })
}, { amount: 0.1 })

// ─────────────────────────────────────────────────────────────────
// HOVER EFFECTS (buttons only — card hover pop removed)
// ─────────────────────────────────────────────────────────────────

function addHoverEffect(selector, inProps, outProps) {
    document.querySelectorAll(selector).forEach(el => {
        el.addEventListener('mouseenter', () => animate(el, inProps,  { duration: 0.22, easing: 'ease-out' }))
        el.addEventListener('mouseleave', () => animate(el, outProps, { duration: 0.22, easing: 'ease-out' }))
    })
}

addHoverEffect('.btn-primary',  { scale: 1.05 }, { scale: 1 })
addHoverEffect('.btn-secondary', { scale: 1.05 }, { scale: 1 })

// Contact icons subtle tilt
document.querySelectorAll('.contact-us li img').forEach(img => {
    img.addEventListener('mouseenter', () => animate(img, { rotate: 15, scale: 1.15 }, { duration: 0.2 }))
    img.addEventListener('mouseleave', () => animate(img, { rotate:  0, scale: 1    }, { duration: 0.2 }))
})

// Video section — ambient pulse on entry
inView('.video-section iframe', el => {
    animate(el, {
        boxShadow: [
            '0 0  0px rgba(21,39,81,0)',
            '0 0 40px rgba(21,39,81,0.2)',
            '0 0  0px rgba(21,39,81,0)',
        ],
    }, { duration: 2, delay: 1, easing: 'ease-in-out' })
}, { amount: 0.5 })

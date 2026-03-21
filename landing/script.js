window.addEventListener('scroll', () => {
    const nav = document.getElementById('navbar');
    if (window.scrollY > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => { entry.isIntersecting && entry.target.classList.add('visible'); });
}, observerOptions);

document.querySelectorAll('.glass-card, .animate-up').forEach(el => observer.observe(el));

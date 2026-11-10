// Equation loader.js'in sadeleştirilmiş hali: yükleme ekranını kaldırır, panel daima koyu temada kalır.
window.addEventListener('load', function () {
    var loader = document.getElementById('load_screen');
    if (loader) loader.remove();
    document.body.classList.add('dark');
});

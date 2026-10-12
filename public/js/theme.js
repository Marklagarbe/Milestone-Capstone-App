(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    document.querySelectorAll('.stat .num, .donut b, .ring b').forEach(function (el) {
        var m = el.textContent.trim().match(/^(\d+)(%?)$/);
        if (!m) return;
        var end = +m[1], suffix = m[2], start = null, duration = 900;
        el.textContent = '0' + suffix;
        function step(t) {
            if (!start) start = t;
            var p = Math.min((t - start) / duration, 1);
            el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))) + suffix;
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });
})();

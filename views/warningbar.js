var auth_outage_countdown = {
    timer: null,
    clienttime: Date.now(),
    siteadmin: false,
    init: function (countdown, siteadmin) {
        this.countdown = countdown;
        this.siteadmin = siteadmin;
        this.divtext = document.getElementById('auth_outage_warningbar_countdown');
        this.divblock = document.getElementById('auth_outage_warningbar_box');
        this.text = this.divtext.innerHTML;
        var $this = this;
        this.timer = setInterval(function () {
            $this.tick();
        }, 1000);
        this.tick();
    },
    tick: function () {
        var elapsed = Math.round((Date.now() - this.clienttime) / 1000);
        var missing = this.countdown - elapsed;
        if (!this.siteadmin && (missing == 10)) {
            this.divblock.className += ' imminent';
            this.divblock.style.height = window.innerHeight + 'px';
        }
        if (missing <= 0) {
            missing = 0;
            clearInterval(this.timer);
            if (!this.siteadmin) {
                location = '/auth/outage/info.php';
            }
        }
        this.divtext.innerHTML = this.text.replace('{{countdown}}', this.seconds2hms(missing));
    },
    seconds2hms: function (seconds) {
        var minutes = Math.floor(seconds / 60);
        var hours = Math.floor(minutes / 60);
        seconds %= 60;
        minutes %= 60;
        // Cross-browser simple solution for padding zeroes.
        if (minutes < 10) {
            minutes = "0" + minutes;
        }
        if (seconds < 10) {
            seconds = "0" + seconds;
        }
        return hours + ':' + minutes + ':' + seconds;
    }
};

// auth_outage_countdown is used outside this js file.
/* jshint unused:false */

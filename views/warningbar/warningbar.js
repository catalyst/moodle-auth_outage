/* exported authOutageWarningBar */
var authOutageWarningBar = {
    init: function(params) {
        this.preview = params.preview;
        this.countdown = params.countdown;
        this.ongoing = params.ongoing;
        this.backonline = params.backonline;
        this.backonlinedescription = params.backonlinedescription;
        this.servertime = params.servertime;
        this.checkfinishedurl = params.checkfinishedurl;
        this.starts = params.starts;
        this.stops = params.stops;
        this.clienttime = Date.now();
        this.finished = false;
        this.divtext = document.getElementById('auth_outage_warningbar_message');
        this.divtitle = document.getElementById('auth_outage_warningbar_title');
        this.divblock = document.getElementById('auth_outage_warningbar_box');
        this.finishbutton = document.getElementById('auth_outage_warningbar_button');
        this.startWarning();
    },

    startWarning: function() {
        if (this.finishbutton) {
            this.finishbutton.style.display = 'none';
        }
        this.divblock.className = 'auth_outage_warning_period';
        this.tickWarning();
    },

    tickWarning: function() {
        var elapsed = Math.round((Date.now() - this.clienttime) / 1000);
        var missing = (this.starts - this.servertime) - elapsed;

        if (missing <= 0) {
            this.startOngoing();
        } else {
            if (missing <= 10) {
                this.divblock.className = 'auth_outage_imminent_period';
            }
            this.divtext.innerHTML = this.countdown.replace('{{countdown}}', this.seconds2hms(missing));

            var $this = this;
            setTimeout(function() {
                $this.tickWarning();
            }, 1000);
        }
    },

    startOngoing: function() {
        this.divblock.className = 'auth_outage_ongoing_period';
        if (this.finishbutton) {
            this.finishbutton.style.display = '';
        }
        this.divtext.innerHTML = this.ongoing;
        this.tickOngoing();
    },

    tickOngoing: function() {
        const MINSECS = 60;

        if (this.finished) {
            return;
        }

        if (this.preview) {
            // If one second before finish time, enfore finish. Otherwise, never finish it.
            if (this.servertime === this.stops - 1) {
                this.finish();
            } else {
                return;
            }
        }

        var $this = this;

        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            $this.ajaxCheckFinished(this);
        };
        xmlhttp.open("GET", this.checkfinishedurl, true);
        xmlhttp.send();

        // Checking if the site is back online every 4-6 minutes.
        var sleepSeconds = 4 * MINSECS + (2 * MINSECS * Math.random());

        setTimeout(function() {
            $this.tickOngoing();
        }, sleepSeconds * 1000);
    },

    ajaxCheckFinished: function(ajax) {
        if (ajax.readyState === XMLHttpRequest.DONE) {
            if (ajax.status === 200) {
                if (ajax.responseText.trim() === 'finished') {
                    this.finish();
                }
            }
        }
    },

    finish: function() {
        this.finished = true;
        this.divblock.className = 'auth_outage_finished_period';
        if (this.finishbutton) {
            this.finishbutton.style.display = 'none';
        }
        this.divtext.innerHTML = this.backonline;
        this.divtitle.innerHTML = this.backonlinedescription;
    },

    seconds2hms: function(seconds) {
        var minutes = Math.floor(seconds / 60);
        var hours = Math.floor(minutes / 60);
        var days = Math.floor(hours / 24);
        seconds %= 60;
        minutes %= 60;
        hours %= 24;
        // Cross-browser simple solution for padding zeroes.
        if (minutes < 10) {
            minutes = "0" + minutes;
        }
        if (seconds < 10) {
            seconds = "0" + seconds;
        }
        if (days > 0) {
            if (days > 1) {
                days = days + ' days ';
            } else {
                days = days + ' day ';
            }
        } else {
            days = '';
        }
        return days + hours + ':' + minutes + ':' + seconds;
    }
};

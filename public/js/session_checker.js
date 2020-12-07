/*
 Проверяет правильную залогиненность и заодно подключение к интернету,
 чтобы при заполнении больших форм информировать о том, что данные могут потеряться.
 */
var SessionChecker = new (function(window) {
	this.config = {
		request_timeout: 10000, // 10 sec
		timer_interval: {
			ok:      30000, // 30 sec
			problem: 3000   // 3 sec
		}
	};
	this.statuses = {
		ok: 1,
		problem: 2
	};

	this.check = function() {
		$.ajax({
			type: "GET",
			url: "/index.php?a=check_session",
			data: { nocache: new Date().getTime() },
			dataType: "json",
			context: this,
			timeout: this.config.request_timeout,
			success: this.success,
			error: this.error
		});
	};

	this.success = function(data, textStatus, jqXHR) {
		if (data.success) {
			this.status = this.statuses.ok;
		} else {
			this.status = this.statuses.problem;
		}
		this.setTimer();
	};

	this.error = function(xhr, status, e) {
		this.status = this.statuses.problem;
		this.setTimer();
	};

	this.setTimer = function() {
		var timer_interval = this.config.timer_interval['problem'];
		var me = this;
		if (this.status == this.statuses.ok) {
			timer_interval = this.config.timer_interval['ok'];
			$(this.block).hide();
		} else {
			$(this.block).show();
		}
		clearInterval(this.interval);
		this.interval = setInterval(function() { me.check(); }, timer_interval);
	};

	this.status = this.statuses.ok;
	this.block = '#session_checker';
	return this;
})(window);

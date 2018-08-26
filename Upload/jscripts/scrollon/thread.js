/**
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this object powers the thread view
 */

var threadScroller = (function($) {
	"use strict";

	var
	$topContainer = {},
	$topSpinner = {},
	$topShowLink,

	$bottomContainer = {},
	$bottomSpinner = {},
	$bottomShowLink,

	$qrLastPid = {},
	$qrFromPage = {},

	// data objects
	options = {
		postsPer: 20,
		defaultPostsPer: 20,
		auto: false,
		live: false,
		liveRate: 30,
		liveDecay: 1.1
	},

	lang = {
		showMore: "Show More Posts"
	},

	refreshRate = 30,
	lastPid = 0,
	gettingPosts = false,

	/**
	 * automatic thread load timer
	 */
	autoThread = (function() {
		/**
		 * add a single-use scroll event handler
		 *
		 * @return void
		 */
		function start() {
			$(window).one("scroll", checkScroll);
		}

		/**
		 * in auto mode, if the EOT marker is in view, check for posts
		 *
		 * @param  Event the scroll event
		 * @return void
		 */
		function checkScroll(e) {
			e.preventDefault();

			if (!options.startOfThread &&
				$topContainer.length &&
				elementInView($topContainer)) {
				checkForPosts(1);
			}

			if (!options.endOfThread &&
				elementInView($bottomContainer)) {
				checkForPosts(2);
			}

			if ((!options.startOfThread ||
				!options.endOfThread) &&
				!gettingPosts) {
				start();
			}
		}

		return {
			start: start,
		};
	})(),

	/**
	 * live thread timer
	 */
	liveThread = (function() {
		var live = false,
			handle = null;

		/**
		 * start live timer
		 *
		 * @return void
		 */
		function start() {
			if (live) {
				return;
			}

			handle = setTimeout(checkForPosts, parseInt(refreshRate, 10) * 1000);
			live = true;
		}

		/**
		 * stop live timer early
		 *
		 * @return void
		 */
		function stop() {
			if (!live) {
				return;
			}

			clearTimeout(handle);
			handle = null;
			live = false;
		}

		return {
			start: start,
			stop: stop,
		};
	})();

	/**
	 * set up the object on window load
	 *
	 * @return void
	 */
	function init() {
		// remove pagination
		$("div.pagination").each(function() {
			this.remove();
		});

		// set up the elements
		if ($("#scrollonTop").length) {
			$topContainer = $("#scrollonTop").show();
			$topSpinner = $("#scrollonSpinnerTop");
		}

		$bottomContainer = $("#scrollonBottom").show();
		$bottomSpinner = $("#scrollonSpinnerBottom");

		if ($("#scrollonShowLinkTop")) {
			$topShowLink = $("#scrollonShowLinkTop");
		}

		if ($("#scrollonShowLinkBottom")) {
			$bottomShowLink = $("#scrollonShowLinkBottom");
		}

		// get specific info for quick reply
		if ($("#lastpid").length) {
			$qrLastPid = $("#lastpid");
		} else {
			$qrLastPid = getFormInput("quick_reply_form", "lastpid");
		}

		if ($qrLastPid.next("input") &&
			$qrLastPid.next("input").prop("name") === "from_page") {
			$qrFromPage = $qrLastPid.next("input");
		} else {
			$qrFromPage = getFormInput("quick_reply_form", "from_page");
		}

		if (options.startOfThread) {
			startOfThread();
		}

		if (options.endOfThread) {
			endOfThread();
		}

		// if auto is off, observe links and get out
		if (options.auto === false) {
			if ($topShowLink.length) {
				$topShowLink.click(checkForPosts);
			}

			if ($bottomShowLink.length) {
				$bottomShowLink.click(checkForPosts);
			}
			return;
		}

		/* automatic mode */

		// remove links
		if ($topShowLink.length) {
			$topShowLink.remove();
			$topShowLink = $topContainer.children("span:first");
			$topShowLink.html(lang.showMore);

		}

		if ($bottomShowLink.length) {
			$bottomShowLink.remove();
			$bottomShowLink = $bottomContainer.children("span:first");
			$bottomShowLink.html(lang.showMore);
		}

		// focus on first post and start auto
		scrollToPost(options.firstPid);
		autoThread.start();
	}

	/**
	 * send an AJAX requests to query for any posts made after
	 * the dateline of the last currently displayed thread
	 *
	 * @param  Event|Number click event; position indicator; or nothing
	 * @return void
	 */
	function checkForPosts(e) {
		var $target, targetId;

		// live is for new threads only
		if (typeof e === "undefined") {
			targetId = "scrollonShowLinkBottom";
		// called directly by autoThread
		} else if (typeof e === "number") {
			targetId = "scrollonShowLinkBottom";
			if (e === 1) {
				targetId = "scrollonShowLinkTop";
			}
		// links
		} else {
			e.preventDefault();

			if (typeof e !== "undefined" &&
				typeof e.target !== "undefined") {
				$target = $(e.target);
			}

			if ($target.length &&
				$target.prop("id").length) {
				targetId = $target.prop("id");
			} else {
				return;
			}
		}

		// pause liveThread if it is running
		liveThread.stop();

		// show the appropriate spinner
		if (targetId == 'scrollonShowLinkTop') {
			$topSpinner.show();
		} else {
			$bottomSpinner.show();
		}

		// this prevents multiple requests at once from the same user
		gettingPosts = true;

		// fetch the posts
		$.ajax({
			type: "post",
			url: "xmlhttp.php",
			data: {
				action: "scrollon",
				mode: targetId,
				tid: options.tid,
				fid: options.fid,
				firstPid: options.firstPid,
				lastPid: options.lastPid,
				postCounterFirst: options.postCounterFirst,
				postCounterLast: options.postCounterLast,
			},
			success: loadPosts,
		});
	}

	/**
	 * handles the server response and either inserts the newly loaded
	 * posts, or flags the threads as ended (EOT)
	 *
	 * @param  Object response
	 * @return void
	 */
	function loadPosts(data) {
		var pidArray, postCount, fromPage, lastPid;

		// allow new requests
		gettingPosts = false;

		// any error indicates no posts or less than a full page
		if (typeof data.error !== "undefined") {
			// set appropriate markers
			if (data.mode === "top") {
				startOfThread();
			} else {
				endOfThread();
			}

			// error 1 is no posts, get out
			if (data.error == 1) {
				return;
			}
		}

		pidArray = data.pids.split(",");
		postCount = pidArray.length;

		// if we found posts, reset the rate to the initial value
		if (postCount) {
			refreshRate = options.liveRate;
		} else {
			refreshRate = refreshRate * options.liveDecay;
		}

		if (data.mode == "top") {
			options.firstPid = data.firstPid;
			options.postCounterFirst = data.postCounterFirst;

			// insert the posts
			$("#posts").prepend(data.posts);

			if (postCount >= 1) {
				scrollToPost(data.lastPid);
			}

			$topSpinner.hide();
		} else {
			options.postCounterLast = data.postCounterLast;

			// figure out which page this puts us on
			fromPage = parseInt(data.postCounterLast / options.defaultPostsPer);
			if (data.postCounterLast % options.defaultPostsPer == 0) {
				fromPage += 1;
			}
			$qrFromPage.val(fromPage);

			// insert the posts
			$("#posts").append(data.posts);

			// if there was at least one post
			if (postCount >= 1) {
				// scroll to the last post that was visible BEFORE the posts loaded
				scrollToPost(options.lastPid);
				$qrLastPid.val(data.lastPid);
			}

			options.lastPid = data.lastPid;
			$bottomSpinner.hide();
		}

		// restart auto mode if applicable
		if (options.auto &&
			(!options.startOfThread ||
			!options.endOfThread)) {
			autoThread.start();
		}

		// restart live mode if applicable
		if (options.live &&
			options.endOfThread) {
			liveThread.start();
		}
	}

	/**
	 * set a marker and hide the top container and spinner
	 *
	 * @return void
	 */
	function startOfThread() {
		options.startOfThread = true;

		if ($topContainer.length) {
			$topContainer.hide();
			$topSpinner.hide();
		}
	}

	/**
	 * set a marker, show no posts text, hide the spinner and
	 * determine whether to start live mode
	 *
	 * @return void
	 */
	function endOfThread() {
		options.endOfThread = true;
		$bottomShowLink.hide();
		$("#scrollonNoPosts").show();
		$bottomSpinner.hide();

		if (options.live) {
			liveThread.start();
		}
	}

	/**
	 * for external set up
	 *
	 * @param Object options
	 * @param Object local language
	 * @return void
	 */
	function setup(adminOptions, language) {
		$.extend(options, adminOptions || {});
		$.extend(lang, language || {});
	}

	/**
	 * detect whether an element is in view
	 *
	 * @param  $el
	 * @return Boolean
	 */
	function elementInView($el) {
		var el = $el[0],
			h = el.getBoundingClientRect().top +
				el.offsetHeight;

		return (h < $(window).height() &&
				h > 0);
	}

	/**
	 * scroll to a specified post
	 *
	 * @return void
	 */
	function scrollToPost(pid) {
		if ($("#post_" + pid)) {
			$("#post_" + pid)[0].scrollIntoView();
		}
	}

	/**
	 * retrieve an input from a specific form by name
	 *
	 * @param  String the id of the form
	 * @param  String the name attribute
	 * @return on success the HTMLInputElement Object on fail an empty object
	 */
	function getFormInput(form, name) {
		var retInput;

		$("#" + form + " input").each(function(k, input) {
			if (input && input.getAttribute("name") === name) {
				retInput = input;
				return;
			}
		});

		return $(retInput);
	}

	$(init);

	// now build the object with only the public methods and properties
	return {
		init: init,
		setup: setup
	};
})(jQuery);

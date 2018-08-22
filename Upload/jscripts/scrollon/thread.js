/**
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this object powers the thread view
 */

var threadScroller = (function($) {
	var container = {},
	noPosts = {},
	spinner = {},
	showLink,
	qrLastPid = {},
	qrFromPage = {},

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
		showMore: 'Show More Posts'
	},

	timer = null,
	refreshRate = 30,
	lastPid = 0;

	/**
	 * set up the object on window load
	 *
	 * @return void
	 */
	function init() {
		if (!checkRequired()) {
			return;
		}

		// remove pagination
		$('div.pagination').each(function() {
			this.remove();
		});

		// set up the elements
		container = $('#scrollon');
		noPosts = $('#scrollon_no_posts');
		spinner = $('#scrollon_spinner');
		container.show();

		if ($('scrollon_show_link')) {
			showLink = $('#scrollon_show_link');
		}

		if ($('#lastpid')) {
			qrLastPid = $('#lastpid');
		} else {
			qrLastPid = getFormInput('quick_reply_form', 'lastpid');
		}

		if (qrLastPid.next('input') &&
			qrLastPid.next('input').prop('name') === 'from_page') {
			qrFromPage = qrLastPid.next('input');
		} else {
			qrFromPage = getFormInput('quick_reply_form', 'from_page');
		}

		// default mode
		if (options.auto === false) {
			if (typeof showLink !== 'undefined') {
				showLink.click(checkForPosts);
			}
			return;
		}

		// auto doesn't need a link
		if (typeof showLink !== 'undefined') {
			showLink.remove();
		}
		showLink = container.children('span:first');
		showLink.html(lang.showMore);
		startAuto();

		if (elementInView()) {
			showLink.hide();
		}
	}

	/**
	 * initiate the observance of mouse scroll for auto mode
	 *
	 * @return void
	 */
	function startAuto() {
		$(window).scroll(checkScroll);
	}

	/**
	 * stop observing of mouse scroll for auto mode
	 *
	 * @return void
	 */
	function stopAuto() {
		$(window).unbind('scroll', checkScroll);
	}

	/**
	 * in auto mode, if the EOT marker is in view, check for posts
	 *
	 * @param  Event the scroll event
	 * @return void
	 */
	function checkScroll(e) {
		e.preventDefault();
		stopAuto();
		if (elementInView()) {
			checkForPosts();
			return;
        }
		startAuto();
	}

	/**
	 * start observing of mouse scroll for live mode
	 *
	 * @return void
	 */
	function startLive() {
		$(window).scroll(checkScrollUpdater);
	}

	/**
	 * stop observing of mouse scroll for live mode
	 *
	 * @return void
	 */
	function stopLive() {
		$(window).unbind('scroll', checkScrollUpdater);
	}

	/**
	 * in live mode, only update when the user is at EOT
	 *
	 * @param  Event the scroll event
	 * @return void
	 */
	function checkScrollUpdater(e) {
		stopLive();
		if (elementInView()) {
			startLiveTimer();
        } else {
			stopLiveTimer();
		}
		startLive();
	}

	/**
	 * start the live mode timer
	 *
	 * @return void
	 */
	function startLiveTimer() {
		stopLiveTimer();
		timer = setTimeout(checkForPosts, parseInt(refreshRate) * 1000);
	}

	/**
	 * stop the live mode timer
	 *
	 * @return void
	 */
	function stopLiveTimer() {
		if (timer !== null) {
			clearTimeout(timer);
			timer = null;
		}
	}

	/**
	 * send an AJAX requests to query for any posts made after
	 * the dateline of the last currently displayed thread
	 *
	 * @param Event the (possibly non-existent) click event
	 * @return void
	 */
	function checkForPosts(e) {
		// sometimes we call this directly so there is no event in progress
		if (typeof e !== 'undefined') {
			e.preventDefault();
		}

		stopLiveTimer();
		spinner.show();

		$.ajax({
			type: 'post',
			url: 'xmlhttp.php',
			data: {
				action: 'scrollon',
				tid: options.tid,
				fid: options.fid,
				lastPostDate: options.lastPostDate,
				postCounter: options.postCounter
			},
			success: loadPosts,
		});
	}

	/**
	 * handles the server response and either inserts the newly loaded
	 * posts, or flags the threads as ended (EOT)
	 *
	 * @param Object response
	 * @return void
	 */
	function loadPosts(data) {
		var pidArray, postCount, fromPage, lastPid;

		// null means there were no new threads
		if (!data) {
			// flag the EOT and then increase the rate according to the delay
			endOfThread();
			refreshRate = refreshRate * options.liveDecay;
		} else {
			// if we found posts, reset the rate to the initial value
			refreshRate = options.liveRate;

			pidArray = data.pids.split(',');
			postCount = pidArray.length;

			options.lastPostDate = data.lastPostDate;
			options.postCounter = data.postCounter;

			// figure out which page this puts us on
			fromPage = parseInt(data.postCounter / options.defaultPostsPer);
			if (data.postCounter % options.defaultPostsPer == 0) {
				fromPage += 1;
			}
			qrFromPage.value = fromPage;

			// insert the posts
			$('#posts').append(data.posts);

			// if we got a 'page full'
			if (postCount == options.postsPer) {
				if (options.auto) {
					startAuto();
				}
			} else {
				endOfThread();
			}

			// if there was at least one post, grab the last pid
			if (postCount >= 1) {
				lastPid = pidArray[postCount - 1];
				scrollToPost(options.lastPid);
				options.lastPid = lastPid;
				qrLastPid.value = lastPid;
			}
		}
		spinner.hide();
	}

	/**
	 * determine whether to end or (if in live mode)
	 * start a timer to check for new posts
	 *
	 * @return void
	 */
	function endOfThread() {
		showLink.hide();
		noPosts.show();

		if (options.live) {
			startLiveTimer();
			startLive();
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
	 * ensure all necessary data is present
	 *
	 * @return Boolean true for all good, false if not
	 */
	function checkRequired() {
		var i, requiredOptions = ['tid', 'fid', 'lastPid', 'lastPostDate'];

		for (i = 0; i < requiredOptions.length; i++) {
			if (typeof options[requiredOptions[i]] === 'undefined' ||
			    parseInt(options[requiredOptions[i]]) === 0) {
				return false;
			}
		}

		if (!$('#scrollon') ||
			!$('#scrollon_no_posts') ||
			!$('#scrollon_spinner')) {
			return false;
		}
		return true;
	}

	/**
	 * elementInView()
	 *
	 * detect whether the element (EOT marker) is in view
	 *
	 * @return Boolean
	 */
	function elementInView() {
		return ((container.get(0).getBoundingClientRect().top +
				container.get(0).offsetHeight) < $(window).height());
	}

	/**
	 * scroll to a specified post
	 *
	 * @return void
	 */
	function scrollToPost(pid) {
		if ($('#post_' + pid)) {
			$('#post_' + pid).get(0).scrollIntoView();
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

		$('#' + form + ' input').each(function(k, input) {
			if (input && input.getAttribute('name') === name) {
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

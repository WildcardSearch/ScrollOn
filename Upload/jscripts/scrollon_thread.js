/**
 * Plugin Name: ScrollOn for MyBB 1.8.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this object powers the thread view
 */

var threadScroller = (function() {
	var version = '1.0.0',
	versionCode = 100,

	// elements
	container = {},
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
	parameters = {},

	timer = null,
	refreshRate = 30,
	lastPid = 0;

	/**
	 * init()
	 *
	 * set up the object on window load
	 *
	 * @return: n/a
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

		if (qrLastPid.next('input') && qrLastPid.next('input').prop('name') === 'from_page') {
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
	 * startAuto()
	 *
	 * initiate the observance of mouse scroll for auto mode
	 *
	 * @return: n/a
	 */
	function startAuto() {
		$(window).scroll(checkScroll);
	}

	/**
	 * stopAuto()
	 *
	 * stop observing of mouse scroll for auto mode
	 *
	 * @return: n/a
	 */
	function stopAuto() {
		$(window).unbind('scroll', checkScroll);
	}

	/**
	 * checkScroll()
	 *
	 * in auto mode, if the EOT marker is in view, check for posts
	 *
	 * @param - Event the scroll event
	 * @return: n/a
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
	 * startLive()
	 *
	 * start observing of mouse scroll for live mode
	 *
	 * @return: n/a
	 */
	function startLive() {
		$(window).scroll(checkScrollUpdater);
	}

	/**
	 * stopLive()
	 *
	 * stop observing of mouse scroll for live mode
	 *
	 * @return: n/a
	 */
	function stopLive() {
		$(window).unbind('scroll', checkScrollUpdater);
	}

	/**
	 * checkScrollUpdater()
	 *
	 * in live mode, only update when the user is at EOT
	 *
	 * @param - Event the scroll event
	 * @return: n/a
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
	 * startLiveTimer()
	 *
	 * start the live mode timer
	 *
	 * @return: n/a
	 */
	function startLiveTimer() {
		stopLiveTimer();
		timer = setTimeout(checkForPosts, parseInt(refreshRate) * 1000);
	}

	/**
	 * stopLiveTimer()
	 *
	 * stop the live mode timer
	 *
	 * @return: n/a
	 */
	function stopLiveTimer() {
		if (timer !== null) {
			clearTimeout(timer);
			timer = null;
		}
	}

	/**
	 * checkForPosts()
	 *
	 * send an AJAX requests to query for any posts made after
	 * the dateline of the last currently displayed thread
	 *
	 * @param - event - (Event) the (possibly non-existent) click event
	 * @return: n/a
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
	 * @param - transport - (Object) the response object
	 * @return: n/a
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
	 * endOfThread()
	 *
	 * determine whether to end or (if in live mode)
	 * start a timer to check for new posts
	 *
	 * @return: n/a
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
	 * setup()
	 *
	 * for external set up
	 *
	 * @param - adminOptions - (Object)
	 * @param - language - (Object)
	 * @return: n/a
	 */
	function setup(adminOptions, language) {
		$.extend(options, adminOptions || {});
		$.extend(lang, language || {});
	}

	/**
	 * checkRequired()
	 *
	 * ensure all necessary data is present
	 *
	 * @return: (Boolean) true for all good, false if not
	 */
	function checkRequired() {
		var i, requiredOptions = ['tid', 'fid', 'lastPid', 'lastPostDate'];

		for (i = 0; i < requiredOptions.length; i++) {
			if (typeof options[requiredOptions[i]] === 'undefined' ||
			    parseInt(options[requiredOptions[i]]) === 0) {
				return false;
			}
		}

		loadParams();
		if (typeof parameters.version != 'undefined' && parameters.version != versionCode) {
			return false;
		}

		if (!$('#scrollon') || !$('#scrollon_no_posts') || !$('#scrollon_spinner')) {
			return false;
		}
		return true;
	}

	/**
	 * getScriptByFileName()
	 *
	 * retrieve a HTMLScriptElement by its src attribute's base filename
	 *
	 * @param - fileName - (String) the unqualified script file name
	 * @return: on success, the element (Object), on fail (Boolean) false
	 */
	function getScriptByFileName(fileName) {
		var returnElement = false;
		$('script').each(function(k, el) {
			if (el.src && el.src.indexOf(fileName) != -1) {
				returnElement = el;
			}
		});
		return returnElement;
	}

	/**
	 * loadParams()
	 *
	 * get the script parameters as passed in the tag src
	 *
	 * @return: n/a
	 */
	function loadParams() {
		var paramParts, params, param, p,
		script = getScriptByFileName('scrollon_thread.js');

		// can't find our script
		if (!script || !script.src) {
			return;
		}

		// split the src into pieces
		params = script.src.split('/');
		if (params.length < 1) {
			// should never happen as browser's tend to autofill http://blah to
			// unqualified src attributes
			return;
		}

		// split the filename on the ? if any
		params = params[params.length - 1];
		params = params.split('?');
		if (params.length < 2) {
			// only one piece means only a filename and no parameters
			return;
		}

		// now discard the filename portion but keep the variable list (if any)
		params = params[1];
		params = params.split('&');
		if (params.length === 0) {
			// idk
			return;
		}

		// now get the params and values
		for (p = 0; p < params.length; p++) {
			param = params[p];
			paramParts = param.split('=');
			if (paramParts.length < 2) {
				// no value
				continue;
			}
			parameters[paramParts[0]] = paramParts[1];
		}
	}

	/**
	 * elementInView()
	 *
	 * detect whether the element (EOT marker) is in view
	 *
	 * @return: n/a
	 */
	function elementInView() {
		return  ((container.get(0).getBoundingClientRect().top + container.get(0).offsetHeight) < getPageSize()[3]);
	}

	/**
	 * scrollToPost()
	 *
	 * scroll to a specified post
	 *
	 * @return: n/a
	 */
	function scrollToPost(pid) {
		if ($('#post_' + pid)) {
			$('#post_' + pid).get(0).scrollIntoView();
		}
	}

	/**
	 * getFormInput()
	 *
	 * retrieve an input from a specific form by name
	 *
	 * @param - form - (String) the id of the form
	 * @param - name - (String) the name attribute
	 * @return: on success the HTMLInputElement Object on fail an empty object
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

	/*
	 * This function is from quirksmode.org
	 * Modified for use in MyBB
	 * Removed in 1.8
	 * ...and now added back for this plugin
	 */
	function getPageSize() {
		var xScroll, 
			yScroll;

		if (window.innerHeight && window.scrollMaxY) {
			xScroll = document.body.scrollWidth;
			yScroll = window.innerHeight + window.scrollMaxY;
		// All but Explorer Mac
		} else if (document.body.scrollHeight > document.body.offsetHeight) {
			xScroll = document.body.scrollWidth;
			yScroll = document.body.scrollHeight;
		// Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
		} else {
			xScroll = document.body.offsetWidth;
			yScroll = document.body.offsetHeight;
		}

		var windowWidth, windowHeight;
		// all except Explorer
		if (self.innerHeight) {
			windowWidth = self.innerWidth;
			windowHeight = self.innerHeight;
		// Explorer 6 Strict Mode
		} else if (document.documentElement &&
				   document.documentElement.clientHeight) {
			windowWidth = document.documentElement.clientWidth;
			windowHeight = document.documentElement.clientHeight;
		// other Explorers
		} else if (document.body) {
			windowWidth = document.body.clientWidth;
			windowHeight = document.body.clientHeight;
		}

		var pageHeight, pageWidth;

		// For small pages with total height less then height of the viewport
		if (yScroll < windowHeight) {
			pageHeight = windowHeight;
		} else {
			pageHeight = yScroll;
		}

		// For small pages with total width less then width of the viewport
		if (xScroll < windowWidth) {
			pageWidth = windowWidth;
		} else {
			pageWidth = xScroll;
		}
		
		var arrayPageSize = new Array(pageWidth, pageHeight, windowWidth, windowHeight);

		return arrayPageSize;
	}

	// now build the object with only the public methods and properties
	return {
		version: version,
		versionCode: versionCode,

		init: init,
		setup: setup
	};
})();
$(document).ready(threadScroller.init);

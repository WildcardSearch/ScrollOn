/**
 * Plugin Name: ScrollOn for MyBB 1.6.x
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
		$$('div.pagination').each(function(e) {
			e.remove();
		});

		// set up the elements
		container = $('scrollon');
		noPosts = $('scrollon_no_posts');
		spinner = $('scrollon_spinner');
		container.show();

		if ($('scrollon_show_link')) {
			showLink = $('scrollon_show_link');
		}

		qrLastPid = getFormInput('quick_reply_form', 'lastpid');

		if ($('lastpid')) {
			qrLastPid = $('lastpid');
		} else {
			qrLastPid = getFormInput('quick_reply_form', 'lastpid');
		}

		if (qrLastPid.next('input') && qrLastPid.next('input').getAttribute('name') === 'from_page') {
			qrFromPage = qrLastPid.next('input');
		} else {
			qrFromPage = getFormInput('quick_reply_form', 'from_page');
		}

		// default mode
		if (options.auto === false) {
			if (typeof showLink !== 'undefined') {
				Event.observe(showLink, 'click', checkForPosts);
			}
			return;
		}

		// auto doesn't need a link
		if (typeof showLink !== 'undefined') {
			showLink.remove();
		}
		showLink = container.down('span');
		showLink.update(lang.showMore);
		startAuto();
	}

	/**
	 * startAuto()
	 *
	 * initiate the observance of mouse scroll for auto mode
	 *
	 * @return: n/a
	 */
	function startAuto() {
		Event.observe(window, 'scroll', checkScroll);
	}

	/**
	 * stopAuto()
	 *
	 * stop observing of mouse scroll for auto mode
	 *
	 * @return: n/a
	 */
	function stopAuto() {
		Event.stopObserving(window, 'scroll', checkScroll);
	}

	/**
	 * checkScroll()
	 *
	 * in auto mode, if the EOT marker is in view, check for posts
	 *
	 * @param - event - (Event) the scroll event
	 * @return: n/a
	 */
	function checkScroll(event) {
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
		Event.observe(window, 'scroll', checkScrollUpdater);
	}

	/**
	 * stopLive()
	 *
	 * stop observing of mouse scroll for live mode
	 *
	 * @return: n/a
	 */
	function stopLive() {
		Event.stopObserving(window, 'scroll', checkScrollUpdater);
	}

	/**
	 * checkScrollUpdater()
	 *
	 * in live mode, only update when the user is at EOT
	 *
	 * @param - event - (Event) the scroll event
	 * @return: n/a
	 */
	function checkScrollUpdater(event) {
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
		timer = checkForPosts.delay(parseInt(refreshRate));
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
	function checkForPosts(event) {
		// sometimes we call this directly so there is no event in progress
		if (typeof event !== 'undefined') {
			Event.stop(event);
		}

		stopLiveTimer();
		spinner.show();

		new Ajax.Request('xmlhttp.php', {
			parameters: {
				action: 'scrollon',
				tid: options.tid,
				fid: options.fid,
				lastPostDate: options.lastPostDate,
				postCounter: options.postCounter
			},
			onSuccess: loadPosts
		});
	}

	/**
	 * loadPosts()
	 *
	 * handles the server response and either inserts the newly loaded
	 * posts, or flags the threads as ended (EOT)
	 *
	 * @param - transport - (Object) the response object
	 * @return: n/a
	 */
	function loadPosts(transport) {
		var info = transport.responseJSON,
		pidArray, postCount, fromPage, lastPid;

		// null means there were no new threads
		if (info === null) {
			// flag the EOT and then increase the rate according to the delay
			endOfThread();
			refreshRate = refreshRate * options.liveDecay;
		} else {
			// if we found posts, reset the rate to the initial value
			refreshRate = options.liveRate;

			pidArray = info.pids.split(',');
			postCount = pidArray.length;

			options.lastPostDate = info.lastPostDate;
			options.postCounter = info.postCounter;

			// figure out which page this puts us on
			fromPage = parseInt(info.postCounter / options.defaultPostsPer);
			if (info.postCounter % options.defaultPostsPer == 0) {
				fromPage += 1;
			}
			qrFromPage.value = fromPage;

			// insert the posts
			$('posts').insert(info.posts);

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
		Object.extend(options, adminOptions || {});
		Object.extend(lang, language || {});
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

		if (!$('scrollon') || !$('scrollon_no_posts') || !$('scrollon_spinner')) {
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
		return $$('script').find(function(script) {
			return (script.src && script.src.indexOf(fileName) != -1);
		});
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
		return  ((container.viewportOffset()[1] + container.offsetHeight) < DomLib.getPageSize()[3]);
	}

	/**
	 * scrollToPost()
	 *
	 * scroll to a specified post
	 *
	 * @return: n/a
	 */
	function scrollToPost(pid) {
		if ($('post_' + pid)) {
			$('post_' + pid).scrollTo();
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
		return $$('#' + form + ' input').find(function(input) {
			return (input && input.getAttribute('name') === name);
		});
	}

	// now build the object with only the public methods and properties ;)
	return {
		version: version,
		versionCode: versionCode,

		init: init,
		setup: setup
	};
})();
Event.observe(window, 'load', threadScroller.init);

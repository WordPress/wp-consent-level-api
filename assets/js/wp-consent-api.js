'use strict';
/**
 * Set consent_type as passed by localize_script, and trigger a hook so geo ip scripts can alter it as needed.
 * It's set on document ready, so the consent management plugin can first add an event listener.
 *
 *
 * Edit: chronologically it seems difficult to create a sort of filter for the consent type.
 * Let's change it so cookiebanners are just required to set it, if it's not available, we use a default, as defined here.
 *
 * This way, if a consent management plugin does not define a consenttype, the one passed here will be used, and it will still work.
 *
 *
 */

window.wp_fallback_consent_type = consent_api.consent_type;
window.waitfor_consent_hook = consent_api.waitfor_consent_hook;

/**
 * Check if a specific service has consent
 * @param {string} service
 */
function wp_has_service_consent(service) {
	//Check if it's in the consented services cookie
	let consented_services_json = consent_api_get_cookie(consent_api.cookie_prefix + '_' + 'consented_services');
	let consented_services;
	try {
		consented_services = JSON.parse(consented_services_json);
	} catch (e) {
		consented_services = {};
	}

	if ( !consented_services.hasOwnProperty( service ) ){
		//default to the category
		const category = wp_get_service_category(service);
		return wp_has_consent( category );
	} else {
		return consented_services[service];
	}
}
/**
 * Check if a specific service is denied
 * @param {string} service
 */
function wp_is_service_denied( service ) {
	//Check if it's in the consented services cookie
	let consented_services_json = consent_api_get_cookie(consent_api.cookie_prefix + '_' + 'consented_services');
	let consented_services;
	try {
		consented_services = JSON.parse(consented_services_json);
	} catch (e) {
		consented_services = {};
	}

	if ( !consented_services.hasOwnProperty( service ) ){
		return false;
	} else {
		return !consented_services[service];
	}
}

/**
 * Set consent for a specific service
 * @param {string} service
 * @param {boolean} consented
 */
function wp_set_service_consent( service, consented ){
	let consented_services_json = consent_api_get_cookie(consent_api.cookie_prefix + '_' + 'consented_services');
	let consented_services;
	try {
		consented_services = JSON.parse(consented_services_json);
	} catch (e) {
		consented_services = {};
	}
	consented_services[service] = consented;
	consent_api_set_cookie(consent_api.cookie_prefix + '_consented_services', JSON.stringify(consented_services));

	let details = {};
	details.service = service;
	details.value = consented;
	let event = new CustomEvent('wp_consent_api_status_change_service', { detail: details });
	document.dispatchEvent(event);
}


/**
 * Check if a user has given consent for a specific category.
 *
 * @param {string} category The item to check consent against.
 */
function wp_has_consent(category) {
	let has_consent = false;
	let consent_type;
    if ( typeof (window.wp_consent_type) !== "undefined" ){
        consent_type = window.wp_consent_type;
    }  else {
        consent_type = window.wp_fallback_consent_type
    }

    let cookie_value = consent_api_get_cookie(consent_api.cookie_prefix + '_' + category);
    if ( !consent_type ) {
        //if consent_type is not set, there's no consent management, we should return true to activate all cookies
        has_consent = true;
    } else if (consent_type.indexOf('optout') !== -1 && cookie_value === '') {
        //if it's opt out and no cookie is set we should also return true
		has_consent = true;
    } else {
        //all other situations, return only true if value is allow
		has_consent = (cookie_value === 'allow');
    }

    return has_consent;
}

/**
 * Retrieve the category of a registered service
 *
 * @param {string} service
 * @returns {string}
 */
function wp_get_service_category( service ) {
	// Check if services array exists and is valid
	if (!consent_api.services || !Array.isArray(consent_api.services)) {
		return 'marketing';
	}

	let services = consent_api.services;
	for (let i = 0; i < services.length; i++) {
		if (services[i] &&
			services[i].name === service &&
			services[i].category) {
			return services[i].category;
		}
	}
	return 'marketing';
}

/**
 * Set cookie by consent type.
 *
 * @param {string} name The cookie name to set.
 * @param {string} value The cookie value to set.
 */
function consent_api_set_cookie(name, value) {
    let secure = ";secure";
	let days = consent_api.cookie_expiration;
	let date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
	let expires = ";expires=" + date.toGMTString();

    if (window.location.protocol !== "https:") secure = '';

    document.cookie = name + "=" + value + secure + expires + ";path=/";
}

/**
 * Retrieve a cookie by name.
 *
 * @param {string} name The name of the cookie to get data from.
 */
function consent_api_get_cookie(name) {
    name = name + "=";
    var cookies = window.document.cookie.split(';');

    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].trim();
        if ( cookie.indexOf(name) === 0 )
            return cookie.substring(name.length, cookie.length);
    }

    //If we get to this point, that means the cookie wasn't found, we return an empty string.
    return "";
}

/**
 * Set a new consent category value.
 *
 * @param {string} category The consent category to update.
 * @param {string} value The value to update the consent category to.
 */
function wp_set_consent(category, value) {
	let event;
	if (value !== 'allow' && value !== 'deny') return;
	var previous_value = consent_api_get_cookie(consent_api.cookie_prefix + '_' + category);
	consent_api_set_cookie(consent_api.cookie_prefix + '_' + category, value);

	//do not trigger a change event if nothing has changed.
	if ( previous_value === value ) return;

	var changedConsentCategory = [];
	changedConsentCategory[category] = value;
	try {
		// For modern browsers except IE:
		event = new CustomEvent('wp_listen_for_consent_change', {detail: changedConsentCategory});
	} catch (err) {
		// If IE 11 (or 10 or 9...?)
		event = document.createEvent('Event');
		event.initEvent('wp_listen_for_consent_change', true, true);
		event.detail = changedConsentCategory;
	}
	// Dispatch/Trigger/Fire the event
	document.dispatchEvent(event);
}




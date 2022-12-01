;(function() {
"use strict";

/**
 * Allow the site theme or other plugins to create analytics tracking events
 *
 * @param {string} type
 * @param {string} category
 * @param {string} action
 * @param {string} label
 * @param {Array}  value
 */
function analyticsTrackingEvent(type, category, action, label) {
  if (typeof wp !== 'undefined') {
    wp.hooks.doAction('minnpostFormProcessorMailchimpAnalyticsEvent', type, category, action, label);
  }
}

/**
 * Allow the plugin to send data to the dataLayer object for Google Tag Manager
 *
 * @param {string} type
 * @param {string} formId
 * @param {string} formClasses
 * @param {string} action
 */
function dataLayerEvent(type, formId, formClasses, action) {
  if (typeof wp !== 'undefined') {
    let dataLayerContent = {
      'event': 'ajaxFormSubmission',
      'type': type,
      'formId': formId,
      'formClasses': formClasses,
      'action': action
    };
    wp.hooks.doAction('minnpostFormProcessorMailchimpDataLayerEvent', dataLayerContent);
  }
}
function shortcodeForm() {
  const mailchimpForms = document.querySelectorAll('.m-form-minnpost-form-processor-mailchimp');
  if (0 < mailchimpForms.length) {
    mailchimpForms.forEach(function (mailchimpForm) {
      let formId = mailchimpForm.id;
      let formClasses = mailchimpForm.className;
      let button = mailchimpForm.querySelector('button');
      let messageElement = mailchimpForm.querySelector('.m-form-message-ajax');
      if (messageElement) {
        button.addEventListener('click', function (event) {
          event.preventDefault(); // Prevent the default form submit.
          event.stopImmediatePropagation();
          button.disabled = true;
          let buttonText = event.target.textContent || event.target.innerText;
          button.innerHTML = 'Processing';
          let data = new FormData(mailchimpForm);
          data.set('ajaxrequest', 'true');
          data.set('subscribe', 'true');
          let message = '';
          let messageClass = 'info';
          let analyticsAction = 'Signup';
          let formType = 'Newsletter';
          fetch(params.ajaxurl, {
            method: 'POST',
            body: data
          }).then(response => response.json()).then(data => {
            if (true === data.success) {
              messageClass = 'info';
              button.innerHTML = 'Thanks';
              switch (data.data.user_status) {
                case 'existing':
                  analyticsAction = 'Update';
                  break;
                case 'new':
                  analyticsAction = 'Signup';
                  break;
                case 'pending':
                  analyticsAction = 'Signup';
                  break;
              }
              if ('' !== data.data.confirm_message) {
                message = data.data.confirm_message;
              }
              if ('function' === typeof analyticsTrackingEvent) {
                analyticsTrackingEvent('event', formType, analyticsAction, location.pathname);
              }
              if ('function' === typeof dataLayerEvent) {
                dataLayerEvent(formType, formId, formClasses, analyticsAction);
              }
            } else {
              messageClass = 'error';
              button.disabled = false;
              button.innerHTML = buttonText;
              if ('function' === typeof analyticsTrackingEvent) {
                analyticsTrackingEvent('event', formType, 'Fail', location.pathname);
              }
              if ('function' === typeof dataLayerEvent) {
                dataLayerEvent(formType, formId, formClasses, 'Fail');
              }
              if ('' !== data.data.confirm_message) {
                message = data.data.confirm_message;
              }
            }
            messageElement.innerHTML = message;
            messageElement.classList.add('m-form-message-' + messageClass);
            messageElement.classList.remove('m-form-message-ajax-placeholder');
            mailchimpForm.classList.add('m-form-minnpost-form-processor-mailchimp-submitted');
            if (mailchimpForm.classList.contains('m-form-fullpage')) {
              messageElement.scrollIntoView({
                behavior: 'smooth'
              });
            }
          }).catch(error => {
            messageElement.innerHTML = '<p>An error has occured. Please try again.</p>';
            messageElement.classList.add('m-form-message-error');
            messageElement.classList.remove('m-form-message-ajax-placeholder');
            mailchimpForm.classList.remove('m-form-minnpost-form-processor-mailchimp-submitted');
            button.disabled = false;
            button.innerHTML = buttonText;
            if ('function' === typeof analyticsTrackingEvent) {
              analyticsTrackingEvent('event', 'Newsletter', 'Fail', location.pathname);
            }
            if ('function' === typeof dataLayerEvent) {
              dataLayerEvent(formType, formId, formClasses, 'Fail');
            }
          });
        });
      }
    });
  }
}
document.addEventListener('DOMContentLoaded', function () {
  shortcodeForm();
});
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNob3J0Y29kZS5qcyJdLCJuYW1lcyI6WyJhbmFseXRpY3NUcmFja2luZ0V2ZW50IiwidHlwZSIsImNhdGVnb3J5IiwiYWN0aW9uIiwibGFiZWwiLCJ3cCIsImhvb2tzIiwiZG9BY3Rpb24iLCJkYXRhTGF5ZXJFdmVudCIsImZvcm1JZCIsImZvcm1DbGFzc2VzIiwiZGF0YUxheWVyQ29udGVudCIsInNob3J0Y29kZUZvcm0iLCJtYWlsY2hpbXBGb3JtcyIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvckFsbCIsImxlbmd0aCIsImZvckVhY2giLCJtYWlsY2hpbXBGb3JtIiwiaWQiLCJjbGFzc05hbWUiLCJidXR0b24iLCJxdWVyeVNlbGVjdG9yIiwibWVzc2FnZUVsZW1lbnQiLCJhZGRFdmVudExpc3RlbmVyIiwiZXZlbnQiLCJwcmV2ZW50RGVmYXVsdCIsInN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbiIsImRpc2FibGVkIiwiYnV0dG9uVGV4dCIsInRhcmdldCIsInRleHRDb250ZW50IiwiaW5uZXJUZXh0IiwiaW5uZXJIVE1MIiwiZGF0YSIsIkZvcm1EYXRhIiwic2V0IiwibWVzc2FnZSIsIm1lc3NhZ2VDbGFzcyIsImFuYWx5dGljc0FjdGlvbiIsImZvcm1UeXBlIiwiZmV0Y2giLCJwYXJhbXMiLCJhamF4dXJsIiwibWV0aG9kIiwiYm9keSIsInRoZW4iLCJyZXNwb25zZSIsImpzb24iLCJzdWNjZXNzIiwidXNlcl9zdGF0dXMiLCJjb25maXJtX21lc3NhZ2UiLCJsb2NhdGlvbiIsInBhdGhuYW1lIiwiY2xhc3NMaXN0IiwiYWRkIiwicmVtb3ZlIiwiY29udGFpbnMiLCJzY3JvbGxJbnRvVmlldyIsImJlaGF2aW9yIiwiY2F0Y2giLCJlcnJvciJdLCJtYXBwaW5ncyI6Ijs7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTQSxzQkFBc0IsQ0FBQ0MsSUFBSSxFQUFFQyxRQUFRLEVBQUVDLE1BQU0sRUFBRUMs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QixFQUFFLFlBQVc7RUFDekRaLGFBQWEsRUFBRTtBQUNoQixDQUFDLENBQUUiLCJmaWxlIjoibWlubnBvc3QtZm9ybS1wcm9jZXNzb3ItbWFpbGNoaW1wLWZyb250LWVuZC5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qKlxuICogQWxsb3cgdGhlIHNpdGUgdGhlbWUgb3Igb3RoZXIgcGx1Z2lucyB0byBjcmVhdGUgYW5hbHl0aWNzIHRyYWNraW5nIGV2ZW50c1xuICpcbiAqIEBwYXJhbSB7c3RyaW5nfSB0eXBlXG4gKiBAcGFyYW0ge3N0cmluZ30gY2F0ZWdvcnlcbiAqIEBwYXJhbSB7c3RyaW5nfSBhY3Rpb25cbiAqIEBwYXJhbSB7c3RyaW5nfSBsYWJlbFxuICogQHBhcmFtIHtBcnJheX0gIHZhbHVlXG4gKi9cbmZ1bmN0aW9uIGFuYWx5dGljc1RyYWNraW5nRXZlbnQodHlwZSwgY2F0ZWdvcnksIGFjdGlvbiwgbGFiZWwpIHtcblx0aWYgKCB0eXBlb2Ygd3AgIT09ICd1bmRlZmluZWQnICkge1xuXHRcdHdwLmhvb2tzLmRvQWN0aW9uKCdtaW5ucG9zdEZvcm1Qcm9jZXNzb3JNYWlsY2hpbXBBbmFseXRpY3NFdmVudCcsIHR5cGUsIGNhdGVnb3J5LCBhY3Rpb24sIGxhYmVsKTtcblx0fVxufVxuXG4vKipcbiAqIEFsbG93IHRoZSBwbHVnaW4gdG8gc2VuZCBkYXRhIHRvIHRoZSBkYXRhTGF5ZXIgb2JqZWN0IGZvciBHb29nbGUgVGFnIE1hbmFnZXJcbiAqXG4gKiBAcGFyYW0ge3N0cmluZ30gdHlwZVxuICogQHBhcmFtIHtzdHJpbmd9IGZvcm1JZFxuICogQHBhcmFtIHtzdHJpbmd9IGZvcm1DbGFzc2VzXG4gKiBAcGFyYW0ge3N0cmluZ30gYWN0aW9uXG4gKi9cbmZ1bmN0aW9uIGRhdGFMYXllckV2ZW50KCB0eXBlLCBmb3JtSWQsIGZvcm1DbGFzc2VzLCBhY3Rpb24gKSB7XG5cdGlmICggdHlwZW9mIHdwICE9PSAndW5kZWZpbmVkJyApIHtcblx0XHRsZXQgZGF0YUxheWVyQ29udGVudCA9IHtcblx0XHRcdCdldmVudCc6ICdhamF4Rm9ybVN1Ym1pc3Npb24nLFxuXHRcdFx0J3R5cGUnOiB0eXBlLFxuXHRcdFx0J2Zvcm1JZCc6IGZvcm1JZCxcblx0XHRcdCdmb3JtQ2xhc3Nlcyc6IGZvcm1DbGFzc2VzLFxuXHRcdFx0J2FjdGlvbic6IGFjdGlvblxuXHRcdH07XG5cdFx0d3AuaG9va3MuZG9BY3Rpb24oJ21pbm5wb3N0Rm9ybVByb2Nlc3Nvck1haWxjaGltcERhdGFMYXllckV2ZW50JywgZGF0YUxheWVyQ29udGVudCApO1xuXHR9XG59XG5cbmZ1bmN0aW9uIHNob3J0Y29kZUZvcm0oKSB7XG5cblx0Y29uc3QgbWFpbGNoaW1wRm9ybXMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCAnLm0tZm9ybS1taW5ucG9zdC1mb3JtLXByb2Nlc3Nvci1tYWlsY2hpbXAnICk7XG5cdGlmICggMCA8IG1haWxjaGltcEZvcm1zLmxlbmd0aCApIHtcblxuXHRcdG1haWxjaGltcEZvcm1zLmZvckVhY2goIGZ1bmN0aW9uICggbWFpbGNoaW1wRm9ybSApIHtcblx0XHRcdGxldCBmb3JtSWQgPSBtYWlsY2hpbXBGb3JtLmlkO1xuXHRcdFx0bGV0IGZvcm1DbGFzc2VzID0gbWFpbGNoaW1wRm9ybS5jbGFzc05hbWU7XG5cdFx0XHRsZXQgYnV0dG9uID0gbWFpbGNoaW1wRm9ybS5xdWVyeVNlbGVjdG9yKCAnYnV0dG9uJyApO1xuXHRcdFx0bGV0IG1lc3NhZ2VFbGVtZW50ID0gbWFpbGNoaW1wRm9ybS5xdWVyeVNlbGVjdG9yKCAnLm0tZm9ybS1tZXNzYWdlLWFqYXgnICk7XG5cdFx0XHRpZiAobWVzc2FnZUVsZW1lbnQgKSB7XG5cdFx0XHRcdGJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKCAnY2xpY2snLCBmdW5jdGlvbiAoIGV2ZW50ICkge1xuXHRcdFx0XHRcdGV2ZW50LnByZXZlbnREZWZhdWx0KCk7IC8vIFByZXZlbnQgdGhlIGRlZmF1bHQgZm9ybSBzdWJtaXQuXG5cdFx0XHRcdFx0ZXZlbnQuc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uKCk7XG5cdFx0XHRcdFx0YnV0dG9uLmRpc2FibGVkID0gdHJ1ZTtcblx0XHRcdFx0XHRsZXQgYnV0dG9uVGV4dCA9IGV2ZW50LnRhcmdldC50ZXh0Q29udGVudCB8fCBldmVudC50YXJnZXQuaW5uZXJUZXh0O1xuXHRcdFx0XHRcdGJ1dHRvbi5pbm5lckhUTUwgPSAnUHJvY2Vzc2luZyc7XG5cdFx0XHRcdFx0bGV0IGRhdGEgPSBuZXcgRm9ybURhdGEoIG1haWxjaGltcEZvcm0gKTtcblx0XHRcdFx0XHRkYXRhLnNldCggJ2FqYXhyZXF1ZXN0JywgJ3RydWUnICk7XG5cdFx0XHRcdFx0ZGF0YS5zZXQoICdzdWJzY3JpYmUnLCAndHJ1ZScgKTtcblxuXHRcdFx0XHRcdGxldCBtZXNzYWdlICAgICAgPSAnJztcblx0XHRcdFx0XHRsZXQgbWVzc2FnZUNsYXNzID0gJ2luZm8nO1xuXHRcdFx0XHRcdGxldCBhbmFseXRpY3NBY3Rpb24gPSAnU2lnbnVwJztcblx0XHRcdFx0XHRsZXQgZm9ybVR5cGUgPSAnTmV3c2xldHRlcic7XG5cblx0XHRcdFx0XHRmZXRjaCggcGFyYW1zLmFqYXh1cmwsIHtcblx0XHRcdFx0XHRcdG1ldGhvZDogJ1BPU1QnLFxuXHRcdFx0XHRcdFx0Ym9keTogZGF0YVxuXHRcdFx0XHRcdH0gKVxuXHRcdFx0XHRcdC50aGVuKCByZXNwb25zZSA9PiByZXNwb25zZS5qc29uKCkgKVxuXHRcdFx0XHRcdC50aGVuKCBkYXRhID0+IHtcblx0XHRcdFx0XHRcdGlmICggdHJ1ZSA9PT0gZGF0YS5zdWNjZXNzICkge1xuXHRcdFx0XHRcdFx0XHRtZXNzYWdlQ2xhc3MgICAgID0gJ2luZm8nO1xuXHRcdFx0XHRcdFx0XHRidXR0b24uaW5uZXJIVE1MID0gJ1RoYW5rcyc7XG5cdFx0XHRcdFx0XHRcdHN3aXRjaCAoIGRhdGEuZGF0YS51c2VyX3N0YXR1cyApIHtcblx0XHRcdFx0XHRcdFx0XHRjYXNlICdleGlzdGluZyc6XG5cdFx0XHRcdFx0XHRcdFx0XHRhbmFseXRpY3NBY3Rpb24gPSAnVXBkYXRlJztcblx0XHRcdFx0XHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRcdFx0XHRcdGNhc2UgJ25ldyc6XG5cdFx0XHRcdFx0XHRcdFx0XHRhbmFseXRpY3NBY3Rpb24gPSAnU2lnbnVwJztcblx0XHRcdFx0XHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRcdFx0XHRcdGNhc2UgJ3BlbmRpbmcnOlxuXHRcdFx0XHRcdFx0XHRcdFx0YW5hbHl0aWNzQWN0aW9uID0gJ1NpZ251cCc7XG5cdFx0XHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRpZiAoICcnICE9PSBkYXRhLmRhdGEuY29uZmlybV9tZXNzYWdlICkge1xuXHRcdFx0XHRcdFx0XHRcdG1lc3NhZ2UgPSBkYXRhLmRhdGEuY29uZmlybV9tZXNzYWdlO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdGlmICggJ2Z1bmN0aW9uJyA9PT0gdHlwZW9mIGFuYWx5dGljc1RyYWNraW5nRXZlbnQgKSB7XG5cdFx0XHRcdFx0XHRcdFx0YW5hbHl0aWNzVHJhY2tpbmdFdmVudCggJ2V2ZW50JywgZm9ybVR5cGUsIGFuYWx5dGljc0FjdGlvbiwgbG9jYXRpb24ucGF0aG5hbWUgKTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRpZiAoICdmdW5jdGlvbicgPT09IHR5cGVvZiBkYXRhTGF5ZXJFdmVudCApIHtcblx0XHRcdFx0XHRcdFx0XHRkYXRhTGF5ZXJFdmVudCggZm9ybVR5cGUsIGZvcm1JZCwgZm9ybUNsYXNzZXMsIGFuYWx5dGljc0FjdGlvbiApO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0XHRtZXNzYWdlQ2xhc3MgICAgID0gJ2Vycm9yJztcblx0XHRcdFx0XHRcdFx0YnV0dG9uLmRpc2FibGVkICA9IGZhbHNlO1xuXHRcdFx0XHRcdFx0XHRidXR0b24uaW5uZXJIVE1MID0gYnV0dG9uVGV4dDtcblx0XHRcdFx0XHRcdFx0aWYgKCAnZnVuY3Rpb24nID09PSB0eXBlb2YgYW5hbHl0aWNzVHJhY2tpbmdFdmVudCApIHtcblx0XHRcdFx0XHRcdFx0XHRhbmFseXRpY3NUcmFja2luZ0V2ZW50KCAnZXZlbnQnLCBmb3JtVHlwZSwgJ0ZhaWwnLCBsb2NhdGlvbi5wYXRobmFtZSApO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdGlmICggJ2Z1bmN0aW9uJyA9PT0gdHlwZW9mIGRhdGFMYXllckV2ZW50ICkge1xuXHRcdFx0XHRcdFx0XHRcdGRhdGFMYXllckV2ZW50KCBmb3JtVHlwZSwgZm9ybUlkLCBmb3JtQ2xhc3NlcywgJ0ZhaWwnICk7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0aWYgKCAnJyAhPT0gZGF0YS5kYXRhLmNvbmZpcm1fbWVzc2FnZSApIHtcblx0XHRcdFx0XHRcdFx0XHRtZXNzYWdlID0gZGF0YS5kYXRhLmNvbmZpcm1fbWVzc2FnZTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0bWVzc2FnZUVsZW1lbnQuaW5uZXJIVE1MID0gbWVzc2FnZTtcblx0XHRcdFx0XHRcdG1lc3NhZ2VFbGVtZW50LmNsYXNzTGlzdC5hZGQoICdtLWZvcm0tbWVzc2FnZS0nICsgbWVzc2FnZUNsYXNzICk7XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5jbGFzc0xpc3QucmVtb3ZlKCAnbS1mb3JtLW1lc3NhZ2UtYWpheC1wbGFjZWhvbGRlcicgKTtcblx0XHRcdFx0XHRcdG1haWxjaGltcEZvcm0uY2xhc3NMaXN0LmFkZCggJ20tZm9ybS1taW5ucG9zdC1mb3JtLXByb2Nlc3Nvci1tYWlsY2hpbXAtc3VibWl0dGVkJyApO1xuXHRcdFx0XHRcdFx0aWYgKCBtYWlsY2hpbXBGb3JtLmNsYXNzTGlzdC5jb250YWlucyggJ20tZm9ybS1mdWxscGFnZScgKSApIHtcblx0XHRcdFx0XHRcdFx0bWVzc2FnZUVsZW1lbnQuc2Nyb2xsSW50b1ZpZXcoe1xuXHRcdFx0XHRcdFx0XHRcdGJlaGF2aW9yOiAnc21vb3RoJ1xuXHRcdFx0XHRcdFx0XHR9KTtcdFx0XHRcdFx0XHRcdFx0XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSApXG5cdFx0XHRcdFx0LmNhdGNoKCBlcnJvciA9PiB7XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5pbm5lckhUTUwgPSAnPHA+QW4gZXJyb3IgaGFzIG9jY3VyZWQuIFBsZWFzZSB0cnkgYWdhaW4uPC9wPic7XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5jbGFzc0xpc3QuYWRkKCAnbS1mb3JtLW1lc3NhZ2UtZXJyb3InICk7XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5jbGFzc0xpc3QucmVtb3ZlKCAnbS1mb3JtLW1lc3NhZ2UtYWpheC1wbGFjZWhvbGRlcicgKTtcblx0XHRcdFx0XHRcdG1haWxjaGltcEZvcm0uY2xhc3NMaXN0LnJlbW92ZSggJ20tZm9ybS1taW5ucG9zdC1mb3JtLXByb2Nlc3Nvci1tYWlsY2hpbXAtc3VibWl0dGVkJyApO1xuXHRcdFx0XHRcdFx0YnV0dG9uLmRpc2FibGVkID0gZmFsc2U7XG5cdFx0XHRcdFx0XHRidXR0b24uaW5uZXJIVE1MID0gYnV0dG9uVGV4dDtcblx0XHRcdFx0XHRcdGlmICggJ2Z1bmN0aW9uJyA9PT0gdHlwZW9mIGFuYWx5dGljc1RyYWNraW5nRXZlbnQgKSB7XG5cdFx0XHRcdFx0XHRcdGFuYWx5dGljc1RyYWNraW5nRXZlbnQoICdldmVudCcsICdOZXdzbGV0dGVyJywgJ0ZhaWwnLCBsb2NhdGlvbi5wYXRobmFtZSApO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0aWYgKCAnZnVuY3Rpb24nID09PSB0eXBlb2YgZGF0YUxheWVyRXZlbnQgKSB7XG5cdFx0XHRcdFx0XHRcdGRhdGFMYXllckV2ZW50KCBmb3JtVHlwZSwgZm9ybUlkLCBmb3JtQ2xhc3NlcywgJ0ZhaWwnICk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSApO1xuXG5cdFx0XHRcdH0pO1xuXHRcdFx0fVxuXHRcdH0gKTtcblx0fVxufVxuXG5kb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCAnRE9NQ29udGVudExvYWRlZCcsIGZ1bmN0aW9uKCkge1xuXHRzaG9ydGNvZGVGb3JtKCk7XG59ICk7XG4iXX0=
}());

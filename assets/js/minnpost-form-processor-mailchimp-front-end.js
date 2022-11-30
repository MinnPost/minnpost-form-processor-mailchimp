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
 * @param {string} action
 */
function dataLayerEvent(type, formId, action) {
  if (typeof wp !== 'undefined') {
    dataLayer = {
      'event': 'formSubmissionSuccess',
      'type': type,
      'formId': formId,
      'action': action
    };
    wp.hooks.doAction('minnpostFormProcessorMailchimpDataLayerEvent', dataLayer);
  }
}
function shortcodeForm() {
  const mailchimpForms = document.querySelectorAll('.m-form-minnpost-form-processor-mailchimp');
  if (0 < mailchimpForms.length) {
    mailchimpForms.forEach(function (mailchimpForm) {
      let formId = mailchimpForm.id;
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
                dataLayerEvent(formType, formId, analyticsAction);
              }
            } else {
              messageClass = 'error';
              button.disabled = false;
              button.innerHTML = buttonText;
              if ('function' === typeof analyticsTrackingEvent) {
                analyticsTrackingEvent('event', formType, 'Fail', location.pathname);
              }
              if ('function' === typeof dataLayerEvent) {
                dataLayerEvent(formType, formId, 'Fail');
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
              dataLayerEvent(formType, formId, 'Fail');
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
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNob3J0Y29kZS5qcyJdLCJuYW1lcyI6WyJhbmFseXRpY3NUcmFja2luZ0V2ZW50IiwidHlwZSIsImNhdGVnb3J5IiwiYWN0aW9uIiwibGFiZWwiLCJ3cCIsImhvb2tzIiwiZG9BY3Rpb24iLCJkYXRhTGF5ZXJFdmVudCIsImZvcm1JZCIsImRhdGFMYXllciIsInNob3J0Y29kZUZvcm0iLCJtYWlsY2hpbXBGb3JtcyIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvckFsbCIsImxlbmd0aCIsImZvckVhY2giLCJtYWlsY2hpbXBGb3JtIiwiaWQiLCJidXR0b24iLCJxdWVyeVNlbGVjdG9yIiwibWVzc2FnZUVsZW1lbnQiLCJhZGRFdmVudExpc3RlbmVyIiwiZXZlbnQiLCJwcmV2ZW50RGVmYXVsdCIsInN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbiIsImRpc2FibGVkIiwiYnV0dG9uVGV4dCIsInRhcmdldCIsInRleHRDb250ZW50IiwiaW5uZXJUZXh0IiwiaW5uZXJIVE1MIiwiZGF0YSIsIkZvcm1EYXRhIiwic2V0IiwibWVzc2FnZSIsIm1lc3NhZ2VDbGFzcyIsImFuYWx5dGljc0FjdGlvbiIsImZvcm1UeXBlIiwiZmV0Y2giLCJwYXJhbXMiLCJhamF4dXJsIiwibWV0aG9kIiwiYm9keSIsInRoZW4iLCJyZXNwb25zZSIsImpzb24iLCJzdWNjZXNzIiwidXNlcl9zdGF0dXMiLCJjb25maXJtX21lc3NhZ2UiLCJsb2NhdGlvbiIsInBhdGhuYW1lIiwiY2xhc3NMaXN0IiwiYWRkIiwicmVtb3ZlIiwiY29udGFpbnMiLCJzY3JvbGxJbnRvVmlldyIsImJlaGF2aW9yIiwiY2F0Y2giLCJlcnJvciJdLCJtYXBwaW5ncyI6Ijs7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTQSxzQkFBc0IsQ0FBQ0MsSUFBSSxFQUFFQyxRQUFRLEVBQUVDLE1BQU0sRUFBRUMsS0FBSyxFQUFFO0VBQzlELElBQUssT0FBT0MsRUFBRSxL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Q0FBRTtZQUNuRixJQUFLcEMsYUFBYSxDQUFDbUMsU0FBUyxDQUFDRyxRQUFRLENBQUUsaUJBQWlCLENBQUUsRUFBRztjQUM1RGxDLGNBQWMsQ0FBQ21DLGNBQWMsQ0FBQztnQkFDN0JDLFFBQVEsRUFBRTtjQUNYLENBQUMsQ0FBQztZQUNIO1VBQ0QsQ0FBQyxDQUFFLENBQ0ZDLEtBQUssQ0FBRUMsS0FBSyxJQUFJO1lBQ2hCdEMsY0FBYyxDQUFDVSxTQUFTLEdBQUcsZ0RBQWdEO1lBQzNFVixjQUFjLENBQUMrQixTQUFTLENBQUNDLEdBQUcsQ0FBRSxzQkFBc0IsQ0FBRTtZQUN0RGhDLGNBQWMsQ0FBQytCLFNBQVMsQ0FBQ0UsTUFBTSxDQUFFLGlDQUFpQyxDQUFFO1lBQ3BFckMsYUFBYSxDQUFDbUMsU0FBUyxDQUFDRSxNQUFNLENBQUUsb0RBQW9ELENBQUU7WUFDdEZuQyxNQUFNLENBQUNPLFFBQVEsR0FBRyxLQUFLO1lBQ3ZCUCxNQUFNLENBQUNZLFNBQVMsR0FBR0osVUFBVTtZQUM3QixJQUFLLFVBQVUsS0FBSyxPQUFPM0Isc0JBQXNCLEVBQUc7Y0FDbkRBLHNCQUFzQixDQUFFLE9BQU8sRUFBRSxZQUFZLEVBQUUsTUFBTSxFQUFFa0QsUUFBUSxDQUFDQyxRQUFRLENBQUU7WUFDM0U7WUFDQSxJQUFLLFVBQVUsS0FBSyxPQUFPM0MsY0FBYyxFQUFHO2NBQzNDQSxjQUFjLENBQUU4QixRQUFRLEVBQUU3QixNQUFNLEVBQUUsTUFBTSxDQUFFO1lBQzNDO1VBQ0QsQ0FBQyxDQUFFO1FBRUosQ0FBQyxDQUFDO01BQ0g7SUFDRCxDQUFDLENBQUU7RUFDSjtBQUNEO0FBRUFJLFFBQVEsQ0FBQ1MsZ0JBQWdCLENBQUUsa0JBQWtCLEVBQUUsWUFBVztFQUN6RFgsYUFBYSxFQUFFO0FBQ2hCLENBQUMsQ0FBRSIsImZpbGUiOiJtaW5ucG9zdC1mb3JtLXByb2Nlc3Nvci1tYWlsY2hpbXAtZnJvbnQtZW5kLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyoqXG4gKiBBbGxvdyB0aGUgc2l0ZSB0aGVtZSBvciBvdGhlciBwbHVnaW5zIHRvIGNyZWF0ZSBhbmFseXRpY3MgdHJhY2tpbmcgZXZlbnRzXG4gKlxuICogQHBhcmFtIHtzdHJpbmd9IHR5cGVcbiAqIEBwYXJhbSB7c3RyaW5nfSBjYXRlZ29yeVxuICogQHBhcmFtIHtzdHJpbmd9IGFjdGlvblxuICogQHBhcmFtIHtzdHJpbmd9IGxhYmVsXG4gKiBAcGFyYW0ge0FycmF5fSAgdmFsdWVcbiAqL1xuZnVuY3Rpb24gYW5hbHl0aWNzVHJhY2tpbmdFdmVudCh0eXBlLCBjYXRlZ29yeSwgYWN0aW9uLCBsYWJlbCkge1xuXHRpZiAoIHR5cGVvZiB3cCAhPT0gJ3VuZGVmaW5lZCcgKSB7XG5cdFx0d3AuaG9va3MuZG9BY3Rpb24oJ21pbm5wb3N0Rm9ybVByb2Nlc3Nvck1haWxjaGltcEFuYWx5dGljc0V2ZW50JywgdHlwZSwgY2F0ZWdvcnksIGFjdGlvbiwgbGFiZWwpO1xuXHR9XG59XG5cbi8qKlxuICogQWxsb3cgdGhlIHBsdWdpbiB0byBzZW5kIGRhdGEgdG8gdGhlIGRhdGFMYXllciBvYmplY3QgZm9yIEdvb2dsZSBUYWcgTWFuYWdlclxuICpcbiAqIEBwYXJhbSB7c3RyaW5nfSB0eXBlXG4gKiBAcGFyYW0ge3N0cmluZ30gZm9ybUlkXG4gKiBAcGFyYW0ge3N0cmluZ30gYWN0aW9uXG4gKi9cbmZ1bmN0aW9uIGRhdGFMYXllckV2ZW50KCB0eXBlLCBmb3JtSWQsIGFjdGlvbiApIHtcblx0aWYgKCB0eXBlb2Ygd3AgIT09ICd1bmRlZmluZWQnICkge1xuXHRcdGRhdGFMYXllciA9IHtcblx0XHRcdCdldmVudCc6ICdmb3JtU3VibWlzc2lvblN1Y2Nlc3MnLFxuXHRcdFx0J3R5cGUnOiB0eXBlLFxuXHRcdFx0J2Zvcm1JZCc6IGZvcm1JZCxcblx0XHRcdCdhY3Rpb24nOiBhY3Rpb25cblx0XHR9O1xuXHRcdHdwLmhvb2tzLmRvQWN0aW9uKCdtaW5ucG9zdEZvcm1Qcm9jZXNzb3JNYWlsY2hpbXBEYXRhTGF5ZXJFdmVudCcsIGRhdGFMYXllciApO1xuXHR9XG59XG5cbmZ1bmN0aW9uIHNob3J0Y29kZUZvcm0oKSB7XG5cblx0Y29uc3QgbWFpbGNoaW1wRm9ybXMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCAnLm0tZm9ybS1taW5ucG9zdC1mb3JtLXByb2Nlc3Nvci1tYWlsY2hpbXAnICk7XG5cdGlmICggMCA8IG1haWxjaGltcEZvcm1zLmxlbmd0aCApIHtcblxuXHRcdG1haWxjaGltcEZvcm1zLmZvckVhY2goIGZ1bmN0aW9uICggbWFpbGNoaW1wRm9ybSApIHtcblx0XHRcdGxldCBmb3JtSWQgPSBtYWlsY2hpbXBGb3JtLmlkO1xuXHRcdFx0bGV0IGJ1dHRvbiA9IG1haWxjaGltcEZvcm0ucXVlcnlTZWxlY3RvciggJ2J1dHRvbicgKTtcblx0XHRcdGxldCBtZXNzYWdlRWxlbWVudCA9IG1haWxjaGltcEZvcm0ucXVlcnlTZWxlY3RvciggJy5tLWZvcm0tbWVzc2FnZS1hamF4JyApO1xuXHRcdFx0aWYgKG1lc3NhZ2VFbGVtZW50ICkge1xuXHRcdFx0XHRidXR0b24uYWRkRXZlbnRMaXN0ZW5lciggJ2NsaWNrJywgZnVuY3Rpb24gKCBldmVudCApIHtcblx0XHRcdFx0XHRldmVudC5wcmV2ZW50RGVmYXVsdCgpOyAvLyBQcmV2ZW50IHRoZSBkZWZhdWx0IGZvcm0gc3VibWl0LlxuXHRcdFx0XHRcdGV2ZW50LnN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbigpO1xuXHRcdFx0XHRcdGJ1dHRvbi5kaXNhYmxlZCA9IHRydWU7XG5cdFx0XHRcdFx0bGV0IGJ1dHRvblRleHQgPSBldmVudC50YXJnZXQudGV4dENvbnRlbnQgfHwgZXZlbnQudGFyZ2V0LmlubmVyVGV4dDtcblx0XHRcdFx0XHRidXR0b24uaW5uZXJIVE1MID0gJ1Byb2Nlc3NpbmcnO1xuXHRcdFx0XHRcdGxldCBkYXRhID0gbmV3IEZvcm1EYXRhKCBtYWlsY2hpbXBGb3JtICk7XG5cdFx0XHRcdFx0ZGF0YS5zZXQoICdhamF4cmVxdWVzdCcsICd0cnVlJyApO1xuXHRcdFx0XHRcdGRhdGEuc2V0KCAnc3Vic2NyaWJlJywgJ3RydWUnICk7XG5cblx0XHRcdFx0XHRsZXQgbWVzc2FnZSAgICAgID0gJyc7XG5cdFx0XHRcdFx0bGV0IG1lc3NhZ2VDbGFzcyA9ICdpbmZvJztcblx0XHRcdFx0XHRsZXQgYW5hbHl0aWNzQWN0aW9uID0gJ1NpZ251cCc7XG5cdFx0XHRcdFx0bGV0IGZvcm1UeXBlID0gJ05ld3NsZXR0ZXInO1xuXG5cdFx0XHRcdFx0ZmV0Y2goIHBhcmFtcy5hamF4dXJsLCB7XG5cdFx0XHRcdFx0XHRtZXRob2Q6ICdQT1NUJyxcblx0XHRcdFx0XHRcdGJvZHk6IGRhdGFcblx0XHRcdFx0XHR9IClcblx0XHRcdFx0XHQudGhlbiggcmVzcG9uc2UgPT4gcmVzcG9uc2UuanNvbigpIClcblx0XHRcdFx0XHQudGhlbiggZGF0YSA9PiB7XG5cdFx0XHRcdFx0XHRpZiAoIHRydWUgPT09IGRhdGEuc3VjY2VzcyApIHtcblx0XHRcdFx0XHRcdFx0bWVzc2FnZUNsYXNzICAgICA9ICdpbmZvJztcblx0XHRcdFx0XHRcdFx0YnV0dG9uLmlubmVySFRNTCA9ICdUaGFua3MnO1xuXHRcdFx0XHRcdFx0XHRzd2l0Y2ggKCBkYXRhLmRhdGEudXNlcl9zdGF0dXMgKSB7XG5cdFx0XHRcdFx0XHRcdFx0Y2FzZSAnZXhpc3RpbmcnOlxuXHRcdFx0XHRcdFx0XHRcdFx0YW5hbHl0aWNzQWN0aW9uID0gJ1VwZGF0ZSc7XG5cdFx0XHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdFx0XHRjYXNlICduZXcnOlxuXHRcdFx0XHRcdFx0XHRcdFx0YW5hbHl0aWNzQWN0aW9uID0gJ1NpZ251cCc7XG5cdFx0XHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdFx0XHRjYXNlICdwZW5kaW5nJzpcblx0XHRcdFx0XHRcdFx0XHRcdGFuYWx5dGljc0FjdGlvbiA9ICdTaWdudXAnO1xuXHRcdFx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0aWYgKCAnJyAhPT0gZGF0YS5kYXRhLmNvbmZpcm1fbWVzc2FnZSApIHtcblx0XHRcdFx0XHRcdFx0XHRtZXNzYWdlID0gZGF0YS5kYXRhLmNvbmZpcm1fbWVzc2FnZTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRpZiAoICdmdW5jdGlvbicgPT09IHR5cGVvZiBhbmFseXRpY3NUcmFja2luZ0V2ZW50ICkge1xuXHRcdFx0XHRcdFx0XHRcdGFuYWx5dGljc1RyYWNraW5nRXZlbnQoICdldmVudCcsIGZvcm1UeXBlLCBhbmFseXRpY3NBY3Rpb24sIGxvY2F0aW9uLnBhdGhuYW1lICk7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0aWYgKCAnZnVuY3Rpb24nID09PSB0eXBlb2YgZGF0YUxheWVyRXZlbnQgKSB7XG5cdFx0XHRcdFx0XHRcdFx0ZGF0YUxheWVyRXZlbnQoIGZvcm1UeXBlLCBmb3JtSWQsIGFuYWx5dGljc0FjdGlvbiApO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0XHRtZXNzYWdlQ2xhc3MgICAgID0gJ2Vycm9yJztcblx0XHRcdFx0XHRcdFx0YnV0dG9uLmRpc2FibGVkICA9IGZhbHNlO1xuXHRcdFx0XHRcdFx0XHRidXR0b24uaW5uZXJIVE1MID0gYnV0dG9uVGV4dDtcblx0XHRcdFx0XHRcdFx0aWYgKCAnZnVuY3Rpb24nID09PSB0eXBlb2YgYW5hbHl0aWNzVHJhY2tpbmdFdmVudCApIHtcblx0XHRcdFx0XHRcdFx0XHRhbmFseXRpY3NUcmFja2luZ0V2ZW50KCAnZXZlbnQnLCBmb3JtVHlwZSwgJ0ZhaWwnLCBsb2NhdGlvbi5wYXRobmFtZSApO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdGlmICggJ2Z1bmN0aW9uJyA9PT0gdHlwZW9mIGRhdGFMYXllckV2ZW50ICkge1xuXHRcdFx0XHRcdFx0XHRcdGRhdGFMYXllckV2ZW50KCBmb3JtVHlwZSwgZm9ybUlkLCAnRmFpbCcgKTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRpZiAoICcnICE9PSBkYXRhLmRhdGEuY29uZmlybV9tZXNzYWdlICkge1xuXHRcdFx0XHRcdFx0XHRcdG1lc3NhZ2UgPSBkYXRhLmRhdGEuY29uZmlybV9tZXNzYWdlO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5pbm5lckhUTUwgPSBtZXNzYWdlO1xuXHRcdFx0XHRcdFx0bWVzc2FnZUVsZW1lbnQuY2xhc3NMaXN0LmFkZCggJ20tZm9ybS1tZXNzYWdlLScgKyBtZXNzYWdlQ2xhc3MgKTtcblx0XHRcdFx0XHRcdG1lc3NhZ2VFbGVtZW50LmNsYXNzTGlzdC5yZW1vdmUoICdtLWZvcm0tbWVzc2FnZS1hamF4LXBsYWNlaG9sZGVyJyApO1xuXHRcdFx0XHRcdFx0bWFpbGNoaW1wRm9ybS5jbGFzc0xpc3QuYWRkKCAnbS1mb3JtLW1pbm5wb3N0LWZvcm0tcHJvY2Vzc29yLW1haWxjaGltcC1zdWJtaXR0ZWQnICk7XG5cdFx0XHRcdFx0XHRpZiAoIG1haWxjaGltcEZvcm0uY2xhc3NMaXN0LmNvbnRhaW5zKCAnbS1mb3JtLWZ1bGxwYWdlJyApICkge1xuXHRcdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5zY3JvbGxJbnRvVmlldyh7XG5cdFx0XHRcdFx0XHRcdFx0YmVoYXZpb3I6ICdzbW9vdGgnXG5cdFx0XHRcdFx0XHRcdH0pO1x0XHRcdFx0XHRcdFx0XHRcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9IClcblx0XHRcdFx0XHQuY2F0Y2goIGVycm9yID0+IHtcblx0XHRcdFx0XHRcdG1lc3NhZ2VFbGVtZW50LmlubmVySFRNTCA9ICc8cD5BbiBlcnJvciBoYXMgb2NjdXJlZC4gUGxlYXNlIHRyeSBhZ2Fpbi48L3A+Jztcblx0XHRcdFx0XHRcdG1lc3NhZ2VFbGVtZW50LmNsYXNzTGlzdC5hZGQoICdtLWZvcm0tbWVzc2FnZS1lcnJvcicgKTtcblx0XHRcdFx0XHRcdG1lc3NhZ2VFbGVtZW50LmNsYXNzTGlzdC5yZW1vdmUoICdtLWZvcm0tbWVzc2FnZS1hamF4LXBsYWNlaG9sZGVyJyApO1xuXHRcdFx0XHRcdFx0bWFpbGNoaW1wRm9ybS5jbGFzc0xpc3QucmVtb3ZlKCAnbS1mb3JtLW1pbm5wb3N0LWZvcm0tcHJvY2Vzc29yLW1haWxjaGltcC1zdWJtaXR0ZWQnICk7XG5cdFx0XHRcdFx0XHRidXR0b24uZGlzYWJsZWQgPSBmYWxzZTtcblx0XHRcdFx0XHRcdGJ1dHRvbi5pbm5lckhUTUwgPSBidXR0b25UZXh0O1xuXHRcdFx0XHRcdFx0aWYgKCAnZnVuY3Rpb24nID09PSB0eXBlb2YgYW5hbHl0aWNzVHJhY2tpbmdFdmVudCApIHtcblx0XHRcdFx0XHRcdFx0YW5hbHl0aWNzVHJhY2tpbmdFdmVudCggJ2V2ZW50JywgJ05ld3NsZXR0ZXInLCAnRmFpbCcsIGxvY2F0aW9uLnBhdGhuYW1lICk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRpZiAoICdmdW5jdGlvbicgPT09IHR5cGVvZiBkYXRhTGF5ZXJFdmVudCApIHtcblx0XHRcdFx0XHRcdFx0ZGF0YUxheWVyRXZlbnQoIGZvcm1UeXBlLCBmb3JtSWQsICdGYWlsJyApO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH0gKTtcblxuXHRcdFx0XHR9KTtcblx0XHRcdH1cblx0XHR9ICk7XG5cdH1cbn1cblxuZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lciggJ0RPTUNvbnRlbnRMb2FkZWQnLCBmdW5jdGlvbigpIHtcblx0c2hvcnRjb2RlRm9ybSgpO1xufSApO1xuIl19
}());

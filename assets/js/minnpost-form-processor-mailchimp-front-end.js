;(function() {
"use strict";

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

function shortcodeForm() {
  var mailchimpForms = document.querySelectorAll('.m-form-minnpost-form-processor-mailchimp');

  if (0 < mailchimpForms.length) {
    mailchimpForms.forEach(function (mailchimpForm) {
      var button = mailchimpForm.querySelector('button');
      var messageElement = mailchimpForm.querySelector('.m-form-message-ajax');

      if (messageElement) {
        button.addEventListener('click', function (event) {
          event.preventDefault(); // Prevent the default form submit.

          event.stopImmediatePropagation();
          button.disabled = true;
          var buttonText = event.target.textContent || event.target.innerText;
          button.innerHTML = 'Processing';
          var data = new FormData(mailchimpForm);
          data.set('ajaxrequest', 'true');
          data.set('subscribe', 'true');
          var message = '';
          var messageClass = 'info';
          var analyticsAction = 'Signup';
          fetch(params.ajaxurl, {
            method: 'POST',
            body: data
          }).then(function (response) {
            return response.json();
          }).then(function (data) {
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
                analyticsTrackingEvent('event', 'Newsletter', analyticsAction, location.pathname);
              }
            } else {
              messageClass = 'error';
              button.disabled = false;
              button.innerHTML = buttonText;

              if ('function' === typeof analyticsTrackingEvent) {
                analyticsTrackingEvent('event', 'Newsletter', 'Fail', location.pathname);
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
          }).catch(function (error) {
            messageElement.innerHTML = '<p>An error has occured. Please try again.</p>';
            messageElement.classList.add('m-form-message-error');
            messageElement.classList.remove('m-form-message-ajax-placeholder');
            mailchimpForm.classList.remove('m-form-minnpost-form-processor-mailchimp-submitted');
            button.disabled = false;
            button.innerHTML = buttonText;

            if ('function' === typeof analyticsTrackingEvent) {
              analyticsTrackingEvent('event', 'Newsletter', 'Fail', location.pathname);
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
}());

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNob3J0Y29kZS5qcyJdLCJuYW1lcyI6WyJ3cCIsIm1haWxjaGltcEZvcm1zIiwiYnV0dG9uIiwiZXZlbnQiLCJkYXRhIiwiZmV0Y2giLCJtZXRob2QiLCJib2R5IiwibWVzc2FnZUNsYXNzIiwiYW5hbHl0aWNzQWN0aW9uIiwibWVzc2FnZSIsImFuYWx5dGljc1RyYWNraW5nRXZlbnQiLCJtZXNzYWdlRWxlbWVudCIsIm1haWxjaGltcEZvcm0iLCJiZWhhdmlvciIsImRvY3VtZW50Iiwic2hvcnRjb2RlRm9ybSJdLCJtYXBwaW5ncyI6Ijs7O0FBQUE7QUFDQTtBQURBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0M7QUFDQ0E7QUFDQTtBQUNEO0FBR0Q7QUFEQTtBQUVDO0FBRUQ7QUFEQztBQUVDQztBQUNDO0FBQ0E7QUFFSDtBQURHO0FBQ0NDO0FBQ0NDO0FBR0w7QUFGS0E7QUFDQUQ7QUFDQTtBQUNBQTtBQUNBO0FBQ0FFO0FBQ0FBO0FBRUE7QUFDQTtBQUNBO0FBRUFDO0FBQ0NDO0FBQ0FDO0FBRnNCO0FBSVI7QUFBQTtBQUVkO0FBQ0NDO0FBQ0FOO0FBRVA7QUFETztBQUNDO0FBQ0NPO0FBQ0E7QUFHVDtBQUZRO0FBQ0NBO0FBQ0E7QUFJVDtBQUhRO0FBQ0NBO0FBQ0E7QUFURjtBQWVQO0FBSk87QUFDQ0M7QUFDQTtBQU1SO0FBTE87QUFDQ0M7QUFDQTtBQUNEO0FBQ0FIO0FBQ0FOO0FBQ0FBO0FBT1A7QUFOTztBQUNDUztBQUNBO0FBUVI7QUFQTztBQUNDRDtBQUNBO0FBQ0Q7QUFTUDtBQVJNRTtBQUNBQTtBQUNBQTtBQUNBQztBQVVOO0FBVE07QUFDQ0Q7QUFDQ0U7QUFENkI7QUFHOUI7QUFDRDtBQUVBRjtBQUNBQTtBQUNBQTtBQUNBQztBQUNBWDtBQUNBQTtBQVVOO0FBVE07QUFDQ1M7QUFDQTtBQUNEO0FBRUQ7QUFDRDtBQUNEO0FBQ0Q7QUFDRDtBQVVEO0FBUkFJO0FBQ0NDO0FBQ0EiLCJmaWxlIjoibWlubnBvc3QtZm9ybS1wcm9jZXNzb3ItbWFpbGNoaW1wLWZyb250LWVuZC5qcyIsInNvdXJjZXNDb250ZW50IjpbIi8qKlxuICogQWxsb3cgdGhlIHNpdGUgdGhlbWUgb3Igb3RoZXIgcGx1Z2lucyB0byBjcmVhdGUgYW5hbHl0aWNzIHRyYWNraW5nIGV2ZW50c1xuICpcbiAqIEBwYXJhbSB7c3RyaW5nfSB0eXBlXG4gKiBAcGFyYW0ge3N0cmluZ30gY2F0ZWdvcnlcbiAqIEBwYXJhbSB7c3RyaW5nfSBhY3Rpb25cbiAqIEBwYXJhbSB7c3RyaW5nfSBsYWJlbFxuICogQHBhcmFtIHtBcnJheX0gIHZhbHVlXG4gKi9cbmZ1bmN0aW9uIGFuYWx5dGljc1RyYWNraW5nRXZlbnQodHlwZSwgY2F0ZWdvcnksIGFjdGlvbiwgbGFiZWwpIHtcblx0aWYgKCB0eXBlb2Ygd3AgIT09ICd1bmRlZmluZWQnICkge1xuXHRcdHdwLmhvb2tzLmRvQWN0aW9uKCdtaW5ucG9zdEZvcm1Qcm9jZXNzb3JNYWlsY2hpbXBBbmFseXRpY3NFdmVudCcsIHR5cGUsIGNhdGVnb3J5LCBhY3Rpb24sIGxhYmVsKTtcblx0fVxufVxuXG5mdW5jdGlvbiBzaG9ydGNvZGVGb3JtKCkge1xuXG5cdGNvbnN0IG1haWxjaGltcEZvcm1zID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCggJy5tLWZvcm0tbWlubnBvc3QtZm9ybS1wcm9jZXNzb3ItbWFpbGNoaW1wJyApO1xuXHRpZiAoIDAgPCBtYWlsY2hpbXBGb3Jtcy5sZW5ndGggKSB7XG5cblx0XHRtYWlsY2hpbXBGb3Jtcy5mb3JFYWNoKCBmdW5jdGlvbiAoIG1haWxjaGltcEZvcm0gKSB7XG5cdFx0XHRsZXQgYnV0dG9uID0gbWFpbGNoaW1wRm9ybS5xdWVyeVNlbGVjdG9yKCAnYnV0dG9uJyApO1xuXHRcdFx0bGV0IG1lc3NhZ2VFbGVtZW50ID0gbWFpbGNoaW1wRm9ybS5xdWVyeVNlbGVjdG9yKCAnLm0tZm9ybS1tZXNzYWdlLWFqYXgnICk7XG5cdFx0XHRpZiAobWVzc2FnZUVsZW1lbnQgKSB7XG5cdFx0XHRcdGJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKCAnY2xpY2snLCBmdW5jdGlvbiAoIGV2ZW50ICkge1xuXHRcdFx0XHRcdGV2ZW50LnByZXZlbnREZWZhdWx0KCk7IC8vIFByZXZlbnQgdGhlIGRlZmF1bHQgZm9ybSBzdWJtaXQuXG5cdFx0XHRcdFx0ZXZlbnQuc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uKCk7XG5cdFx0XHRcdFx0YnV0dG9uLmRpc2FibGVkID0gdHJ1ZTtcblx0XHRcdFx0XHRsZXQgYnV0dG9uVGV4dCA9IGV2ZW50LnRhcmdldC50ZXh0Q29udGVudCB8fCBldmVudC50YXJnZXQuaW5uZXJUZXh0O1xuXHRcdFx0XHRcdGJ1dHRvbi5pbm5lckhUTUwgPSAnUHJvY2Vzc2luZyc7XG5cdFx0XHRcdFx0bGV0IGRhdGEgPSBuZXcgRm9ybURhdGEoIG1haWxjaGltcEZvcm0gKTtcblx0XHRcdFx0XHRkYXRhLnNldCggJ2FqYXhyZXF1ZXN0JywgJ3RydWUnICk7XG5cdFx0XHRcdFx0ZGF0YS5zZXQoICdzdWJzY3JpYmUnLCAndHJ1ZScgKTtcblxuXHRcdFx0XHRcdGxldCBtZXNzYWdlICAgICAgPSAnJztcblx0XHRcdFx0XHRsZXQgbWVzc2FnZUNsYXNzID0gJ2luZm8nO1xuXHRcdFx0XHRcdGxldCBhbmFseXRpY3NBY3Rpb24gPSAnU2lnbnVwJztcblxuXHRcdFx0XHRcdGZldGNoKCBwYXJhbXMuYWpheHVybCwge1xuXHRcdFx0XHRcdFx0bWV0aG9kOiAnUE9TVCcsXG5cdFx0XHRcdFx0XHRib2R5OiBkYXRhXG5cdFx0XHRcdFx0fSApXG5cdFx0XHRcdFx0LnRoZW4oIHJlc3BvbnNlID0+IHJlc3BvbnNlLmpzb24oKSApXG5cdFx0XHRcdFx0LnRoZW4oIGRhdGEgPT4ge1xuXHRcdFx0XHRcdFx0aWYgKCB0cnVlID09PSBkYXRhLnN1Y2Nlc3MgKSB7XG5cdFx0XHRcdFx0XHRcdG1lc3NhZ2VDbGFzcyAgICAgPSAnaW5mbyc7XG5cdFx0XHRcdFx0XHRcdGJ1dHRvbi5pbm5lckhUTUwgPSAnVGhhbmtzJztcblx0XHRcdFx0XHRcdFx0c3dpdGNoICggZGF0YS5kYXRhLnVzZXJfc3RhdHVzICkge1xuXHRcdFx0XHRcdFx0XHRcdGNhc2UgJ2V4aXN0aW5nJzpcblx0XHRcdFx0XHRcdFx0XHRcdGFuYWx5dGljc0FjdGlvbiA9ICdVcGRhdGUnO1xuXHRcdFx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0XHRcdFx0Y2FzZSAnbmV3Jzpcblx0XHRcdFx0XHRcdFx0XHRcdGFuYWx5dGljc0FjdGlvbiA9ICdTaWdudXAnO1xuXHRcdFx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0XHRcdFx0Y2FzZSAncGVuZGluZyc6XG5cdFx0XHRcdFx0XHRcdFx0XHRhbmFseXRpY3NBY3Rpb24gPSAnU2lnbnVwJztcblx0XHRcdFx0XHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdGlmICggJycgIT09IGRhdGEuZGF0YS5jb25maXJtX21lc3NhZ2UgKSB7XG5cdFx0XHRcdFx0XHRcdFx0bWVzc2FnZSA9IGRhdGEuZGF0YS5jb25maXJtX21lc3NhZ2U7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0aWYgKCAnZnVuY3Rpb24nID09PSB0eXBlb2YgYW5hbHl0aWNzVHJhY2tpbmdFdmVudCApIHtcblx0XHRcdFx0XHRcdFx0XHRhbmFseXRpY3NUcmFja2luZ0V2ZW50KCAnZXZlbnQnLCAnTmV3c2xldHRlcicsIGFuYWx5dGljc0FjdGlvbiwgbG9jYXRpb24ucGF0aG5hbWUgKTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0bWVzc2FnZUNsYXNzICAgICA9ICdlcnJvcic7XG5cdFx0XHRcdFx0XHRcdGJ1dHRvbi5kaXNhYmxlZCAgPSBmYWxzZTtcblx0XHRcdFx0XHRcdFx0YnV0dG9uLmlubmVySFRNTCA9IGJ1dHRvblRleHQ7XG5cdFx0XHRcdFx0XHRcdGlmICggJ2Z1bmN0aW9uJyA9PT0gdHlwZW9mIGFuYWx5dGljc1RyYWNraW5nRXZlbnQgKSB7XG5cdFx0XHRcdFx0XHRcdFx0YW5hbHl0aWNzVHJhY2tpbmdFdmVudCggJ2V2ZW50JywgJ05ld3NsZXR0ZXInLCAnRmFpbCcsIGxvY2F0aW9uLnBhdGhuYW1lICk7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0aWYgKCAnJyAhPT0gZGF0YS5kYXRhLmNvbmZpcm1fbWVzc2FnZSApIHtcblx0XHRcdFx0XHRcdFx0XHRtZXNzYWdlID0gZGF0YS5kYXRhLmNvbmZpcm1fbWVzc2FnZTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0bWVzc2FnZUVsZW1lbnQuaW5uZXJIVE1MID0gbWVzc2FnZTtcblx0XHRcdFx0XHRcdG1lc3NhZ2VFbGVtZW50LmNsYXNzTGlzdC5hZGQoICdtLWZvcm0tbWVzc2FnZS0nICsgbWVzc2FnZUNsYXNzICk7XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5jbGFzc0xpc3QucmVtb3ZlKCAnbS1mb3JtLW1lc3NhZ2UtYWpheC1wbGFjZWhvbGRlcicgKTtcblx0XHRcdFx0XHRcdG1haWxjaGltcEZvcm0uY2xhc3NMaXN0LmFkZCggJ20tZm9ybS1taW5ucG9zdC1mb3JtLXByb2Nlc3Nvci1tYWlsY2hpbXAtc3VibWl0dGVkJyApO1xuXHRcdFx0XHRcdFx0aWYgKCBtYWlsY2hpbXBGb3JtLmNsYXNzTGlzdC5jb250YWlucyggJ20tZm9ybS1mdWxscGFnZScgKSApIHtcblx0XHRcdFx0XHRcdFx0bWVzc2FnZUVsZW1lbnQuc2Nyb2xsSW50b1ZpZXcoe1xuXHRcdFx0XHRcdFx0XHRcdGJlaGF2aW9yOiAnc21vb3RoJ1xuXHRcdFx0XHRcdFx0XHR9KTtcdFx0XHRcdFx0XHRcdFx0XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSApXG5cdFx0XHRcdFx0LmNhdGNoKCBlcnJvciA9PiB7XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5pbm5lckhUTUwgPSAnPHA+QW4gZXJyb3IgaGFzIG9jY3VyZWQuIFBsZWFzZSB0cnkgYWdhaW4uPC9wPic7XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5jbGFzc0xpc3QuYWRkKCAnbS1mb3JtLW1lc3NhZ2UtZXJyb3InICk7XG5cdFx0XHRcdFx0XHRtZXNzYWdlRWxlbWVudC5jbGFzc0xpc3QucmVtb3ZlKCAnbS1mb3JtLW1lc3NhZ2UtYWpheC1wbGFjZWhvbGRlcicgKTtcblx0XHRcdFx0XHRcdG1haWxjaGltcEZvcm0uY2xhc3NMaXN0LnJlbW92ZSggJ20tZm9ybS1taW5ucG9zdC1mb3JtLXByb2Nlc3Nvci1tYWlsY2hpbXAtc3VibWl0dGVkJyApO1xuXHRcdFx0XHRcdFx0YnV0dG9uLmRpc2FibGVkID0gZmFsc2U7XG5cdFx0XHRcdFx0XHRidXR0b24uaW5uZXJIVE1MID0gYnV0dG9uVGV4dDtcblx0XHRcdFx0XHRcdGlmICggJ2Z1bmN0aW9uJyA9PT0gdHlwZW9mIGFuYWx5dGljc1RyYWNraW5nRXZlbnQgKSB7XG5cdFx0XHRcdFx0XHRcdGFuYWx5dGljc1RyYWNraW5nRXZlbnQoICdldmVudCcsICdOZXdzbGV0dGVyJywgJ0ZhaWwnLCBsb2NhdGlvbi5wYXRobmFtZSApO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH0gKTtcblxuXHRcdFx0XHR9KTtcblx0XHRcdH1cblx0XHR9ICk7XG5cdH1cbn1cblxuZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lciggJ0RPTUNvbnRlbnRMb2FkZWQnLCBmdW5jdGlvbigpIHtcblx0c2hvcnRjb2RlRm9ybSgpO1xufSApO1xuIl19

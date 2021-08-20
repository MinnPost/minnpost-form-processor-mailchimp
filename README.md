# MinnPost Form Processor for Mailchimp
MinnPost runs a form processor plugin that passes data to and from Mailchimp. This plugin handles the user interface for those forms.

## Shortcode syntax

When adding a form with this plugin, place a `newsletter_form` shortcode with the desired attributes into WordPress.

### All attributes

- `placement`: Where this shortcode is being used. Possible values are: `fullpage`, `instory`, `inpopup`, `useraccount`, `usersummary`, or `sidebar`.
- `groups_available`: This is for what Mailchimp groups the form should make available for the user. Possible values: `default` (loads the plugin settings), `all`, supply a csv of group names, or add any given list name individuall (ex `artscape`). If there are groups the user is not able to choose in this instance, they should be left out.
- `show_elements`: Possible values are `title` or `description`. The default is based on where the plugin is being placed. Title and description elements are shown by default on `fullpage` forms, but this can be overridden by templates in the theme.
- `hide_elements`: Possible values are `title`, `description`. The default is based on where the plugin is being placed.
- `button_text`: Button text for the form. The default value is 1) whatever is in the plugin settings, 2) if that is blank, "Subscribe".
- `button_styles`: Takes CSS styles for the button. This value will be inlined, if present.
- `image_url`: If a local image url is specified, it will be added before the `content_before` value.
- `image_alt`: If adding an image, alt text should also be added.
- `content_before`: This value displays before the form markup, but inside the same container. Default is empty. It can take HTML, but special characters have to be encoded to avoid messing with the shortcode parsing.
- `content_after`: This content displays after the form markup, but inside the same container. Default is empty. It can take HTML, but special characters have to be encoded to avoid messing with the shortcode parsing.
- `in_content_label`: The label text on the email field. Default is empty.
- `in_content_label_placement`: If there is a label on form, this sets where it goes. Value can be `before` or `after`. By default this is used on `frontpage` placed forms, where the label isn't shown by default, but can be positioned with this attribute.
- `confirm_message`: Text that is shown to the user after a successful form submission. The default is in the plugin settings, but it can be customized for specific usage.
- `error_message`: Text that is shown to the user after form submission if there is an error. The default is in the plugin settings, but it can be customized for specific usage.
- `categories`: I'm not really sure what this does. Probably should remove it. In the code, it's documented as: categories corresponding to groups. default is empty.
- `classes`: classes for CSS and JavaScript to target. If there are values here, they will be added to the `<form>` (or other first markup element) in the template.
- `redirect_url`: by default, this value is the current URL.

## Templates

This plugin comes with templates for several placement options that change the design based on where the form is being used, and basic CSS for each. If the general pattern works, use it. If it does not, override any of the templates by creating a `minnpost-form-processor-mailchimp-templates` folder in the active WordPress theme, and include a copy of the template. Then, change the PHP structure to match the specific needs.

### Available templates

- `archive`: when the form is being added to an archive page (category, tag, author archive, date archive, etc).
- `frontpage`: when the form is on the home page. This is generally meant to be a full width element.
- `fullpage`: when the form is by itself (or mostly by itself). This is meant to be a newsletter sign up landing page.
- `inpopup`: when the form is used in a popup.
- `instory`: when the form is used in the flow of a post or page.
- `useraccount`: when the form is used on a user account page (ex if there is a page to set email preferences for a logged in user).
- `usersummary`: when there is a static list of subscribed newsletters for a logged in user, but it's not a form that is submitted.
- `widget`: when the form is used on a widget, for example in a custom HTML widget.

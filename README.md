SSTools :: Extra Stuff For SilverStripe CMS
===========================================

This is an ongoing collection of classes and components that
I've used on different projects and wish were part of Sapphire.

Components
----------

 * __SMTP Mailer:__ drop-in for the default sapphire mailer that uses 
   PHPMailer for authenticated SMTP instead of php's mail() function.
 * __LivePub:__ some additional helper classes for injecting limited
   amounts of dynamic code into php files published using the built-
   in static publishing capabilities of SilverStripe. Allows you
   to do things like a shopping cart dashboard while still using
   static publishing.
 * __CachedWidget:__ just make your widget extend this class instead
   of Widget and it's output will automatically be cached
 * __UrlPagingDataObjectSet:__ works just like a normal dataobjectset
   but instead of using /blog?start=10 for paging, it uses URL segments like
   /blog/2.
 * SSTools: base namespace for utility functions
 * Forms/FieldTypes: additional form stuff. Some of it may be out of date with
   newer versions of SilverStripe. These are not very well tested at
   the minute, but some are useful for niche cases. Use at your own risk.

Installation: SMTP Mailer
-------------------------

*Add to _config.php:*
	$mailer = new SmtpMailer('mail.server.com', 'username', 'password');
	Email::set_mailer($mailer);

Included in this class is also a ProcessedEmail class that can be dropped
in for the standard Email class. It uses Emogrifier to automatically
inline CSS styles (must be in a <style> tag)

Installation: LivePub
---------------------

1. Follow basic staticpublisher instructions at http://doc.silverstripe.org/staticpublisher
   *NOTE: you have to use 'php' not 'html' caching. Also, note that it has only been tested
   with filesystempublisher*
2. Comment out or remove cache management stuff in _cms/code/staticpublisher/CachedPHPPage.tmpl_
   (only \*\*CONTENT\*\* is required)
3. In _cms/code/staticpublisher/FilesystemPublisher.php_, apply the appropriate patch from
   the _sstools/code/livepub/patches_ folder (or simply follow the instructions in the 
   MANUAL.md file in the same folder)
4. Wrap anything that you don't want cached (is_ajax, session, etc) using eval_php, exec_php,
   include_php, ViewableWrapper or controller hooks.
5. If you want the default template hooks, add this to _mysite/_config.php_
		Object::add_extension("Page_Controller", "LivePubHelper_ControllerHooks");

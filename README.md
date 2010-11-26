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


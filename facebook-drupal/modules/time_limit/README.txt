Time Limit
----------

This module was developed while working on client's site who wanted to keep site
freely available for user only for limited time. So basically, anyone who wants
to show site content for some limited time and then force users to register -
could use this module.

For example, site providing reviews for some product category:
- User comes to site and browse all reviews available
- After 30 minutes he is redirected to page saying, that to continue browsing
site he need to submit new review and register (this could be achieved with
inline_registration module)

Another use - provide 'trial' period for new user to browse site and then ask
them to register where they will need to pay for membership. The good point
there - users do not have to register first to obtain 'trial' role. They obtains
trial status right after they come to the site.

This time limit could be easily avoided by user, so this should be used on sites
not relying heavily on this, but I believe 99% users will not bother with
clearing cookies in order to get access to the site. And I believe this module
will be used mostly on sites with free registrations.

Important part - search engines will crawl site freely since they do not store
sessions and therefore all content from the site will be searchable in major
search engines. So opposing to approach with registering user and giving him
'trial' role, in this case all content will be indexed by search engines.

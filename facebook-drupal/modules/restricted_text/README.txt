// $Id: README.txt,v 1.2 2008/12/08 21:35:15 pukku Exp $

The Restricted Text module allows users to restrict access to
parts of a node body, based on various restrictions. This is
implemented as an input filter, so it will only work where
other input filters work.

Please note that using this filter prevents the filter mechanism
from caching text for that format. If you wish to use this filter
on a site with a heavy load, I suggest creating a separate input
format with this filter so that you can only use it where it is
needed.

The filter allows users to surround blocks of text with [restrict] and
[/restrict], which will only allow authenticated users to see the enclosed
text. Alternately, the author can use [restrict:roles=(comma-separated list of roles)]
to restrict to other roles. Examples:
    [restrict:roles=Editors]
    [restrict:roles=Site 1,Site 2]
    [restrict:roles=Programming Group,QA Department]

Other modules can provide alternate restriction schemes using Drupal's
hook mechanism. For more information on this, see the implementation
of hook_restricted_text_access() in the module.

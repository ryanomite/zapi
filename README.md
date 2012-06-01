zapi
====

A generic Zend Framework stack for a flexible RESTful API

## Basic Usage

Zapi provides a RESTful API interface, building on the Zend_Rest_Route router to forward to actions by HTTP verb. Further, Zapi provides different output types available through different extensions.

For example, a POST to /admin/user.xml will invoke the postAction of the UserController of the Admin module, rendering it using the XML layout (layouts/xml.phtml)

At this time Zapi supports three different response types:

* json (default) - Javascript Object Notation
* xml - XML document. Please note that the <record> element is used for dataset records.
* csv - Comma-delimited text (2-dimensional datasets only)


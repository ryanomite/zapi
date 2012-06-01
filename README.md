zapi
====

A generic Zend Framework stack that provides a flexible RESTful API service, extending the Zend_Rest_Route by adding the ability to request data in different formats with an extension in the URL (for example /sample-api.xml). Zapi also offers support for token-based authentication, though it's up to the developer to implement the actual token validation.

Zapi also provides generic ZF support for database access, caching, and graph outputs. Exceptions are automatically caught, and handled in the same format as other API calls.

## Supported output formats

Zapi offers a wide variety of output formats, including:

* json
* xml
* xls (Microsoft (R) Excel 2003) - NOTE: PEAR dependencies must be installed
* csv
* txt (Tab-delimited)
* png (Basic graphs, using the Google Chart API)
* html (unformatted)
* data (PHP-serialized output)
* table (HTML table)

# Change Log

### 3.0.1

* Updated: dependencies
* Added:   missing changelog entries

### 3.0.0

* Changed:  now just a wrapper for Slim JWT plugin with some routes

### 2.0.4

* Fixed:    Reverting previous change on LoginProvider as the previous change prevented dependency injection from being used 

### 2.0.3

* Added:    Added logic to be able to expire a Token

### 2.0.2

* Fixed:    Fixed issue with Incorrect LoginProvider being used

### 2.0.1

* Added:    Support to catch LoginExpired and Login Locked Out exceptions and throw a relevant response

### 2.0.0

* Changed:  RestApi module upgrade

### 1.1.1
* Added:    ApiSettings class
* Changed:  Token expiration time is pulled from settings. Old constant deprecated
* Added:    Support for extending token expiration on use

### 1.1.0

* Added:	Changelog
* Changed:	Support for Rhubarb 1.1

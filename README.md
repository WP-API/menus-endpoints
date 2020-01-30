# WordPress REST API Navigation Menus Endpoints

[![Build Status](https://travis-ci.org/WP-API/menus-endpoints.svg?branch=master)](https://travis-ci.org/WP-API/menus-endpoints)

Feature plugin implementing REST endpoints for WordPress Navigation Menus, Menus Items and Menu locations. 
The origins of this projects can be found at trac ticket [#40878](https://core.trac.wordpress.org/ticket/40878).

To access data from these API, request must be authenticated, not reliving possibility senative data that is not public by default in WordPress.  

### Endpoints

Endpoints to define for menus:

```
GET  /menus
POST  /menus
GET  /menus/:id
POST /menus/:id
DELETE /menus/:id
```

Endpoints to define for menu items:

```
GET  /menu-items
POST  /menu-items
GET  /menu-items/:id
POST /menu-items/:id
DELETE /menu-items/:id
```


Endpoints to define for menu locations:

```
GET  /menu-items
GET  /menu-items/:location
```

### License

The Menus Endpoints is licensed under the GPL v2 or later.

> This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

> This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

> You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


### Contributions

Anyone is welcome to contribute to Menus Endpoints

There are various ways you can contribute:

* Raise an issue on GitHub.
* Send us a Pull Request with your bug fixes and/or new features.
* Provide feedback and suggestions on enhancements.

It is worth noting that, this project has travis enabled and runs automated tests, including code sniffing and unit tests. Any pull request will be rejects, unless these tests pass. This is to ensure that the code is of the highest quality, follows coding standards and is secure.

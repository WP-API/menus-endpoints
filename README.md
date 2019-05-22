# WP-API: Nav Menus and Widgets Endpoints

Feature plugin for Nav Menus and Widgets Endpoints

Endpoints to define for widgets:

```
GET  /widgets
GET  /widgets/:type
POST /widgets/:type
GET  /widget-types
GET  /widgets/:type/:number
PUT  /widgets/:type/:number
```

Endpoints defined for menus:

```
OPT     /menus
GET     /menus
POST    /menus

OPT     /menus/:id
GET     /menus/:id
POST    /menus/:id
DEL     /menus/:id

OPT     /menus/:id/settings
GET     /menus/:id/settings
POST    /menus/:id/settings

OPT     /menu-items
GET     /menu-items
POST    /menu-items

OPT     /menu-items/:id
GET     /menu-items/:id
POST    /menu-items/:id
DEL     /menu-items/:id

OPT     /menu-locations
GET     /menu-locations

OPT     /menu-locations/:location
GET     /menu-locations/:location
POST    /menu-locations/:location
```

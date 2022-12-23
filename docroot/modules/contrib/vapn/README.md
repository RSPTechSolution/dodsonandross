### Introduction ###

View Access Per Node (vapn) is a very simple access control module with
relation to **viewing** content on a per-node basis, the initial idea is a porting of the D8 module "View Permissions Per Node" 
but more features/integrations are planned for this project.

**Note:** This module only deals with viewing nodes, it does not affect other
op's (eg. create/update/delete).

There are a lot of access control modules, many of them are compared
[here](https://www.drupal.org/node/270000).

This module only uses hook_node_access so it should play *fairly* well with other access control modules.

### Installation ###

- [Enable](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules) as
  usual,
- Set permissions for users that can use and administer vapn.

### Configuration ###

- Navigate to `admin/config/vapn/vapnconfig` and select the content types,
- Create or edit a node with one of the types selected above,
- There will be a new vertical tab named *View Access per node*,
- Select the roles that will have view access to this node.

#### Notes ####

- Roles with the *bypass node access* permission will not be listed,
- Selecting no roles will skip using this module for access control,
- Selecting even one role will enable this module for access control, and deny
  access to any users without one of the selected roles.

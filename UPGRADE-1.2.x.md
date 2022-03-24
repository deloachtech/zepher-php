UPGRADE 1.x
===========

Upgrade v1.1.* to v1.2.*
------------------------

Add the following to your zepher_dev.json
```json
{
  "_feature_access": [
    "Enter a feature:role value to add/override configured options.",
    "An asterisk (*) indicates any feature/role. (e.g. *:* would grant everyone assess to everything.)"
  ],
  "feature_access": {
    "*": "ROLE_SUPER_USER"
  }
}

```
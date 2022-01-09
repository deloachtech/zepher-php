# Zepher

Add customer and user access control to your codebase.

Zepher works by compiling complex configurations of RBAC and SaaS versioning (e.g. Basic, Plus, Premium) into feature-based enforcement.

You define everything at `zepher.io` and download the compiled `zepher.json` file for use in your codebase.

    if($zepher->userCanAccess('FEATURE_FOO', $userRoles)){

        // User with a role specified has access to the feature.
    }

    if($zepher->userCanAccess('FEATURE_FOO', $userRoles, 'PERMISSION_READ')){

        // User with a role and the permission specified has access to the feature.
    }

The logic above will enforce role _and_ versioning restrictions.

See https://zepher.io for more information.

## Prerequisites

An account at https://zepher.io where you define requirements and download the object file. 

## Installation

Zepher compiles information into a universal `zepher.json` object file for processing in any language. (You can roll your own processor.) 

This is the `PHP` version of the `zepher.json` object file processor.

You can install it via composer:

    composer/require deloachtech/zepher

Or download the package at https://github.com/deloachtech/zepher.

## Usage

There are three variables from the` zepher.json` file for assignment (using your existing codebase logic).

1. Your accounts are assigned a domain (e.g. Bank, Hospital).
2. Your accounts are assigned (select) a domain version (e.g. Basic, Plus, Premium).
3. Your account users are assigned roles (e.g. Admin, Super, Clerk).


Instantiate the Zepher class and begin. Different data persistence classes are provided, or you can create your own.

    $zepher = new Zepher($accountId, $domainId, new FilesystemPersistenceClass(), $configDir);

    // To offer domain version selection
    $versions = $zepher->getDomainVersions();

    // Or during signup
    $versions = $zepher->getSignupDomains();

    // To assign user roles
    $roles = $zepher->getRoles();

    // To enforce everything (RBAC and domain versioning)
    if($zepher->userCanAccess('SOME_FEATURE', $userRoles, 'SOME_PERMISSION')){
        //...
    }

See https://docs.zepher.io for more information.


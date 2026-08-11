<?php

namespace App\Constants\Messages;

final class UserMessage
{
    public const USER_NOT_FOUND = 'User not found.';

    public const CANNOT_DELETE_ADMIN = 'Cannot delete admin user.';

    public const USER_LIST_RETRIEVED = 'User list retrieved successfully.';

    public const USER_CREATED = 'User created successfully.';

    public const USER_DETAILS_RETRIEVED = 'User details retrieved successfully.';

    public const USER_UPDATED = 'User updated successfully.';

    public const USER_DELETED = 'User deleted successfully.';

    public const USER_STATUS_UPDATED = 'User status updated successfully.';

    public const CANNOT_MODIFY_LAST_ADMIN = 'Cannot change role or deactivate the last active Admin in the system!';
}

<?php

return [

    /**
     * When false, `app:deploy-seed` exits without running seeders. Set in production to disable
     * without removing the command from the Docker entrypoint.
     */
    'seed_on_deploy' => filter_var(
        env('SEED_ON_DEPLOY', 'true'),
        FILTER_VALIDATE_BOOLEAN
    ),

];

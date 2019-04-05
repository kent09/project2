<?php

#test interface
$router->get('/', function () use ($router) {
    return response()->json('API Server is Working.', 200);
});

$router->get('/confirm-withdrawal/{key}', ['uses' => 'Bank\BankController@confirm_withdrawal']);

$router->get('/confirm-earnings-withdrawal/{key}', ['uses' => 'Bank\Membership\MembershipController@confirm_withdrawal']);

#end test interface

$router->group(['middleware' => 'cors' ], function () use ($router) { #wrap all route with cors middleware
    $router->group(['prefix' => 'api'], function () use ($router) {
        $router->group(['prefix' => 'bank', 'namespace' => 'Bank'], function () use ($router) {
            $router->post('/request-resync', ['uses' => 'BankController@request_resync']);
        });

        $router->group(['prefix' => 'task', 'namespace' => 'Task'], function () use ($router) {
            $router->post('/available-for-bot', ['uses' => 'TaskController@available_for_bot']);
            $router->post('/related-for-bot', ['uses' => 'TaskController@related_for_bot']);
        });
    });

    $router->group(['prefix' => 'social'], function () use ($router) {

        $router->post('/facebook', ['uses' => 'AuthController@facebookRedirect']);
        $router->post('/facebook-callback', ['uses' => 'AuthController@facebookCallback']);

        $router->post('/twitter', ['uses' => 'AuthController@twitterRedirect']);
        $router->post('/twitter-callback', ['uses' => 'AuthController@twitterCallback']);

        $router->post('/google', ['uses' => 'AuthController@googleRedirect']);
        $router->post('/google-callback', ['uses' => 'AuthController@googleCallback']);

        $router->post('/linkedin', ['uses' => 'AuthController@linkedinRedirect']);
        $router->post('/linkedin-callback', ['uses' => 'AuthController@linkedinCallback']);

        $router->post('/2fa/enable', ['uses' => 'Google2FAController@enableTwoFactor']);
        $router->post('/2fa/disable', ['uses' => 'Google2FAController@disableTwoFactor']);
        $router->post('/2fa/register', ['uses' => 'Google2FAController@registerTwoFactor']);

        $router->post('/2fa/validate',['middleware' => ['throttle'], 'uses' => 'AuthController@postValidateToken']);

        $router->post('/2fa/list', ['uses' => 'Google2FAController@twoFaList']);
        $router->post('/2fa/reset', ['uses' => 'Google2FAController@twoFaReset']);

    });

    $router->group(['prefix' => 'auth'], function () use ($router) {

        $router->post('/login', ['uses' => 'AuthController@login']);
        $router->post('/facebook-login', ['uses' => 'AuthController@facebookLogin']);
        $router->post('/facebook-register', ['uses' => 'AuthController@registerViaFacebook']);
        $router->post('/register', ['uses' => 'AuthController@register']);
        $router->post('/check-referral-code', ['uses' => 'AuthController@checkReferralCode']);
        $router->post('/request-device', ['uses' => 'AuthController@requestDevice']);
        $router->get('/verify-email/{token}', ['uses' => 'AuthController@verifyEmail']);
        $router->get('/check-username/{username}', ['uses' => 'AuthController@checkUsername']);
        $router->post('/check-email', ['uses' => 'AuthController@checkEmail']);
        $router->post('/forgot-password', ['uses' => 'AuthController@forgotPassword']);
        $router->post('/reset-password', ['uses' => 'AuthController@resetPassword']);
        $router->post('/connect-social',['uses' => 'AuthController@socialConnect']);


    });

    // remove wizard
    $router->group([ 'prefix' => 'api', 'middleware' => ['jwt', 'auth'] ], function () use ($router) {

        $router->post('/save-referrer', ['uses' => 'AuthController@saveReferrer']);
        
        #Wizard Module
        $router->group(['prefix' => '/wizard', 'namespace' => 'Wizard'], function() use ($router) {
            $router->post('/check-social-auth', ['uses' => 'WizardController@socialAuthStatus']);
            $router->post('/account-agree', ['uses' => 'WizardController@confirmWizardAccount']);
        });
        #End Wizard

        $router->post('/get-voting-weight', ['uses' => 'Bot\BotController@get_voting_weight']);

        $router->post('/refresh-token', ['uses' => 'AuthController@refresh_token']);

        #User Module
        $router->group(['prefix' => '/user', 'namespace' => 'User'], function() use ($router) {
            $router->post('/follow-user', ['uses' => 'UserController@follow', 'middleware' => ['permissions:follow-user']]);
            $router->post('/member-search', ['uses' => 'UserController@memberSearch', 'middleware' => ['permissions:member-search']]);
        });
        #End User

        #Task Module
        $router->group(['prefix' => '/task','namespace' => 'Task'], function() use ($router) {
            #read
            $router->post('/task-all-search', ['uses' => 'TaskController@allTaskSearch', 'middleware' => ['permissions:search-tasks']]);
            $router->get('/task-list', ['uses' => 'TaskController@index', 'middleware' => ['permissions:view-tasks']]);
            $router->get('/task-active-count', ['uses' => 'TaskController@countActiveTask', 'middleware' => ['permissions:view-tasks']]);
            $router->post('/task-own-list', ['uses' => 'TaskController@ownTask', 'middleware' => ['permissions:view-own-tasks']]);
            $router->post('/task-own-count', ['uses' => 'TaskController@countOwnTask', 'middleware' => ['permissions:view-own-tasks']]);
            $router->post('/task-hidden', ['uses' => 'TaskController@hiddenTask', 'middleware' => ['permissions:view-hidden-tasks']]);
            $router->post('/task-hidden-count', ['uses' => 'TaskController@countHiddenTask', 'middleware' => ['permissions:view-hidden-tasks']]);
            $router->post('/task-completed', ['uses' => 'TaskController@completedTask', 'middleware' => ['permissions:view-completed-tasks']]);
            $router->post('/task-completed-count', ['uses' => 'TaskController@countCompletedTask', 'middleware' => ['permissions:view-completed-tasks']]);
            $router->post('/task-edit', ['uses' => 'TaskController@editTask', 'middleware' => ['permissions:edit-task']]);
            $router->post('/task-show', ['uses' => 'TaskController@showTask', 'middleware' => ['permissions:view-task']]);

            $router->post('/task-link-image-tag', ['uses' => 'TaskController@getImageMetatag']);
            
            $router->post('/task-detail',['uses' => 'TaskController@taskDetails', 'middleware' => ['permissions:view-task']]);
            $router->post('/task-completer-list', ['uses' => 'TaskController@taskCompleterList', 'middleware' => ['permissions:view-task-completers']]);
            $router->post('/task-revoke-completer-list', ['uses' => 'TaskController@taskRevokeCompleterList', 'middleware' => ['permissions:view-task-revokes']]);
            $router->post('/task-history', ['uses' => 'TaskController@taskHistory', 'middleware' => ['permissions:view-task-histories']]);
            $router->post('/task-blocked-users', ['uses' => 'TaskController@taskBlockedUsers', 'middleware' => ['permissions:view-task-blocked-users']]);

            $router->post('/task-comments', ['uses' => 'TaskController@taskComments', 'middleware' => ['permissions:view-task-comments']]);
            $router->post('/task-comment-count', ['uses' => 'TaskController@countTaskComments']);
            $router->post('/task-comment-upload', ['uses' => 'TaskController@taskCommentUploadImage', 'middleware' => ['permissions:upload-task-comment']]);
            $router->post('/task-comment-save', ['uses' => 'TaskController@saveTaskComment', 'middleware' => ['permissions:save-task-comment']]);
            $router->post('/task-comment-reply-save', ['uses' => 'TaskController@saveTaskSubComment', 'middleware' => ['permissions:save-task-comment']]);
            $router->post('/task-comment-update', ['uses' => 'TaskController@updateTaskComment', 'middleware' => ['permissions:edit-comment']]);
            $router->post('/task-comment-delete', ['uses' => 'TaskController@deleteTaskComment', 'middleware' => ['permissions:delete-comment']]);
            $router->post('/task-comment-specific', ['uses' => 'TaskController@specificTaskComment', 'middleware' => ['permissions:view-task-comment']]);

            #search
            $router->post('/task-list-search', ['uses' => 'TaskController@searchTaskList', 'middleware' => ['permissions:search-tasks']]);
            #write
            $router->post('/task-create', ['uses' => 'TaskController@createTask', 'middleware' => ['permissions:create-task']]);
            $router->post('/task-update', ['uses' => 'TaskController@updateTask', 'middleware' => ['permissions:edit-task']]);
            $router->post('/task-hide', ['uses' => 'TaskController@hideTask', 'middleware' => ['permissions:hide-task']]);
            $router->post('/task-unhide', ['uses' => 'TaskController@unHideTask', 'middleware' => ['permissions:unhide-task']]);
            $router->post('/task-delete', ['uses' => 'TaskController@deleteTask', 'middleware' => ['permissions:delete-task']]);
            $router->post('/task-activate', ['uses' => 'TaskController@activateTask', 'middleware' => ['permissions:activate-task']]);
            $router->post('/task-deactivate', ['uses' => 'TaskController@deActivateTask', 'middleware' => ['permissions:deactivate-task']]);
            $router->post('/task-complete', ['uses' => 'TaskController@completeTask', 'middleware' => ['permissions:complete-task']]);
            $router->post('/generate-task-url', ['uses' => 'TaskController@generateTaskUrl']);
            $router->post('/revoke-user', ['uses' => 'TaskController@revokeUserTask', 'middleware' => ['permissions:revoke-task-user']]);
            $router->post('/block-user', ['uses' => 'TaskController@blockUserTask', 'middleware' => ['permissions:block-task-user']]);
            $router->post('/task-attachment-view', ['uses' => 'TaskController@viewTaskAttachment']);
            $router->post('/task-fee-charge', ['uses' => 'TaskController@getTaskFeeCharge']);
            $router->post('/task-requirement-limitation', ['uses' => 'TaskController@getRequirementLimitation']);
            $router->post('/task-free-task', ['uses' => 'TaskController@getFreeTaskCount']);
        });
        #End Task

        #Bank Module
        $router->group(['prefix' => '/bank', 'namespace' => 'Bank'], function () use ($router) {
            #READ
            $router->post('/bank-index', ['uses' => 'BankController@index', 'middleware' => ['permissions:view-bank']]);
            $router->post('/balances', ['uses' => 'BankController@getBalances', 'middleware' => ['permissions:view-bank']]);
            $router->post('/debug-balances', ['uses' => 'BankController@debug_getBalances', 'middleware' => ['permissions:view-bank']]);
            $router->post('/btc-tab', ['uses' => 'BankController@btcTab', 'middleware' => ['permissions:view-bank']]);

            #basic and ledger
            $router->group(['middleware' => ['permissions:view-ledger']], function () use ($router) {
                $router->post('/basic-ledger/deposit', ['uses' => 'BankController@depositBasicHistory']);
                $router->post('/basic-ledger/withdrawal', ['uses' => 'BankController@withdrawalBasicHistory']);
                $router->post('/basic-ledger/coin-ledger', ['uses' => 'BankController@coinLedgerHistory']);
            });
            
            $router->group(['prefix' => '/history'], function () use ($router) {
                # task reward history
                $router->group(['middleware' => ['permissions:view-bank-task']], function () use ($router) {
                    $router->post('/task-reward/completion', ['uses' => 'BankController@taskCompletionRewardHistory']);
                    $router->post('/task-reward/revoke', ['uses' => 'BankController@taskRevokeRewardHistory']);
                    $router->post('/task-reward/withdrawal', ['uses' => 'BankController@taskWithdrawalHistory']);
                });
                
                # referral task points history
                $router->group(['middleware' => ['permissions:view-bank-referral']], function () use ($router) {
                    $router->post('/referral/task-points/direct', ['uses' => 'BankController@directReferralPointsHistory']);
                    $router->post('/referral/task-points/second', ['uses' => 'BankController@secondReferralPointsHistory']);
                    $router->post('/referral/task-points/third', ['uses' => 'BankController@thirdReferralPointsHistory']);

                    $router->post('/referral/referral/signup-reward', ['uses' => 'BankController@referralDefaultSignupRewardHistory']);
                    $router->post('/referral/referral/social-connect-reward', ['uses' => 'BankController@referralSocialConnectRewardHistory']);
                });

                $router->group(['middleware' => ['permissions:view-bank-gift']], function () use ($router) {
                    # gift coin history
                    $router->post('/gift-coin/deposit', ['uses' => 'BankController@gitfCoinDepositHistory']);
                    $router->post('/gift-coin/withdrawal', ['uses' => 'BankController@giftCoinWithdrawalHistory']);
                });
                
                # bonus coins history
                $router->group(['middleware' => ['permissions:view-bank-bonus']], function () use ($router) {
                    $router->post('/bonus-coins/monthly-bonus-coins', ['uses' => 'BankController@bonusCoinsDepositHistory']);
                    $router->post('/bonus-coins/social-connect', ['uses' => 'BankController@socialConnectDepositHistory']);
                });

                # option trade history
                $router->group(['middleware' => ['permissions:view-bank-trade']], function () use ($router) {
                    $router->post('/option-trade/deposit', ['uses' => 'BankController@optionTradeDepositHistory']);
                    $router->post('/option-trade/withdrawal', ['uses' => 'BankController@optionTradeWithdrawalHistory']);
                });

                $router->post('/blog-payout/deposit', ['uses' => 'BankController@blogPayoutDepositHistory', 'middleware' => ['permissions:view-bank-blog']]);
                $router->post('/membership-earnings', ['uses' => 'BankController@membershipEarningsHistory', 'middleware' => ['permissions:view-membership-earnings']]);
            });
            
            #write
            $router->post('/withdraw', ['uses' => 'BankController@withdraw', 'middleware' => ['permissions:withdraw-bank']]);
            $router->post('/cancel-withdraw', ['uses' => 'BankController@cancel_withdraw', 'middleware' => ['permissions:cancel-withdraw-bank']]);
            $router->post('/resync', ['uses' => 'BankController@resync', 'middleware' => ['permissions:resync-bank']]);


            # BITCOIN 
            $router->post('/btc-withdraw', ['uses' => 'BankController@bitcoinWithdraw', 'middleware' => ['permissions:withdraw-bank']]);
            
            $router->group(['middleware' => ['permissions:view-bank-btc']], function () use ($router) {
                $router->post('/btc-info', ['uses' => 'BankController@bitcoinInfo']);
                $router->post('/basic-ledger/btc/deposit', ['uses' => 'BankController@btcDeposit']);
                $router->post('/basic-ledger/btc/withdrawal', ['uses' => 'BankController@btcWithdrawal']);
                $router->post('/basic-ledger/btc/option-trade', ['uses' => 'BankController@btcOptionTrade']);
                $router->post('/basic-ledger/btc/option-trade', ['uses' => 'BankController@btcOptionTrade']);
            });

            $router->post('/btc-resync', ['uses' => 'BankController@btcResync', 'middleware' => ['permissions:resync-bank-btc']]);
            $router->post('/btc-create-wallet', ['uses' => 'BankController@btcCreateWallet', 'middleware' => ['permissions:create-btc-wallet']]);

            $router->group(['prefix' => 'membership', 'namespace' => 'Membership'], function () use ($router) {
                $router->post('/balance', 'MembershipController@balance');
                $router->post('/request-withdraw', 'MembershipController@request_withdraw');
                $router->post('/withdrawal-history', 'MembershipController@withdrawal_history');
                $router->post('/billing-history', 'MembershipController@billing_history');
            });

        });
        #End Bank

        # Membership Start
        $router->group(['prefix' => 'membership', 'namespace' => 'Membership'], function () use ($router) {
            #read
            $router->post('/list-roles', ['uses' => 'MembershipController@list_roles']);
            #write
            $router->post('/apply', ['uses' => 'MembershipController@apply']);
            $router->post('/application-status', ['uses' => 'MembershipController@application_status']);
            $router->post('/use-code', ['uses' => 'MembershipController@use_code']);
            $router->post('/check-code', ['uses' => 'MembershipController@check_code']);

            $router->post('/user-limitations', ['uses' => 'MembershipController@getUserLimitations']);
        });
        # Membership End

        #MANAGER START
        $router->group(['prefix' => 'manager', 'namespace' => 'Manager', 'middleware' => ['roles:admin']], function() use ($router) {
            #USER START
            $router->group(['prefix' => 'user', 'namespace' => 'User'], function() use ($router) {
                #Read
                $router->post('/all-users', ['uses' => 'UserController@get_all_users']);
                $router->post('/filter-users', ['uses' => 'UserController@get_filtered_users']);
                $router->post('/search', ['uses' => 'UserController@search']);
                $router->post('/counts', ['uses' => 'UserController@user_counts']);

                $router->get('/account-summary/{username}', ['uses' => 'UserController@accountSummary']);
                $router->get('/statistics', ['uses' => 'UserController@getStatistics']);
                $router->get('/device-count', ['uses' => 'UserController@deviceCount']);
                $router->get('/banned-reasons/{user_id}', ['uses' => 'UserController@bannedReasons']);
                $router->post('/ban', ['uses' => 'UserController@banUser']);
                $router->post('/unban', ['uses' => 'UserController@unbanUser']);
                $router->post('/disable', ['uses' => 'UserController@disableUser']);
                $router->post('/activate', ['uses' => 'UserController@activateUser']);
                $router->post('/activate-disable-multi', ['uses' => 'UserController@setStatusMulti']);
                $router->post('/ban-multi', ['uses' => 'UserController@banUserMulti']);
                $router->post('/unban-multi', ['uses' => 'UserController@unbanUserMulti']);

            });

            $router->group(['prefix' => 'social-connect', 'namespace' => 'User'], function () use ($router) {
                $router->post('/status-count', ['uses' => 'UserController@countSocialConStatus']);
                $router->post('/all-list', ['uses' => 'UserController@socialConnectAll']);
                $router->post('/hard-unlink-request-list', ['uses' => 'UserController@hardUnlinkRequestList']);
                $router->post('/hard-unlinked-list', ['uses' => 'UserController@hardUnlinkedList']);
                $router->post('/soft-unlinked-list', ['uses' => 'UserController@softUnlinkedList']);
            });

            $router->group(['prefix' => 'referral'], function () use ($router) {
                $router->get('/settings', ['uses' => 'ReferralController@index']);
                $router->post('/task-point/settings-history', ['uses' => 'ReferralController@taskPointSettingsHistory']);
                $router->post('/signup-reward/settings-history', ['uses' => 'ReferralController@signupRewardSettingsHistory']);
                $router->post('/social-connect/settings-history', ['uses' => 'ReferralController@socialConnectSettingsHistory']);
                $router->post('/set-settings', ['uses' => 'ReferralController@setReferralSettings']);
                $router->post('/change-request', ['uses' => 'ReferralController@referralChangeRequest']);
                $router->post('/change-request-list', ['uses' => 'ReferralController@referralChangeRequestList']);
                $router->post('/change-history', ['uses' => 'ReferralController@referralChangeHistory']);
                $router->post('/approve-referral-change', ['uses' => 'ReferralController@approveReferralChange']);
                $router->post('/decline-referral-change', ['uses' => 'ReferralController@declineReferralChange']);
            });

            $router->group(['prefix' => 'bank'], function () use ($router) {
                $router->get('/settings', ['uses' => 'BankController@index']);
                $router->post('/sup-for-approval', ['uses' => 'BankController@supForApproval']);
                $router->post('/btc-for-approval', ['uses' => 'BankController@btcForApproval']);
                $router->post('/withdrawal/sup/approve/{id}', ['uses' => 'BankController@approveSupWithdrawal']);
                $router->post('/withdrawal/sup/decline/{id}', ['uses' => 'BankController@declineSupWithdrawal']);
                $router->post('/withdrawal/btc/approve/{id}', ['uses' => 'BankController@approveBtcWithdrawal']);
                $router->post('/withdrawal/btc/decline/{id}', ['uses' => 'BankController@declineBtcWithdrawal']);
            });

            $router->group(['prefix' => 'task'], function () use ($router) {
                $router->post('/revoke-list', ['uses' => 'BankController@taskRevokeList']);
                $router->post('/reinstate-reward', ['uses' => 'BankController@reinstateReward']);
                $router->post('/creator-stats', ['uses' => 'BankController@taskCreatorStats']);
            });

            $router->group(['prefix' => 'faqs'], function () use ($router) {
                $router->post('/', ['uses' => 'FaqsController@index']);
                $router->post('/create', ['uses' => 'FaqsController@createFaqs']);
                $router->post('/update', ['uses' => 'FaqsController@updateFaqs']);
                $router->post('/delete', ['uses' => 'FaqsController@deleteFaqs']);
            });
            
            
            #USER END
        });
        #MANAGER END

        # LEADERBOARD START
        $router->group(['prefix' => 'leaderboard',  'namespace' => 'LeaderBoard'], function () use ($router) {
            $router->post('/general', ['uses' => 'LeaderBoardController@general']);
            $router->post('/own', ['uses' => 'LeaderBoardController@own']);
            $router->post('/referral', ['uses' => 'LeaderBoardController@referral']);
        });
        # LEADERBOARD END

        # NOTIFICATION START
        $router->group(['prefix' => 'notification',  'namespace' => 'Notification'], function () use ($router) {
            $router->post('/user', ['uses' => 'NotificationController@userNofification']);
            $router->post('/count-all', ['uses' => 'NotificationController@countAllNotifications']);
            $router->post('/delete', ['uses' => 'NotificationController@deleteNotification']);
            $router->post('/view-all', ['uses' => 'NotificationController@showAll']);
            $router->post('/check-status', ['uses' => 'NotificationController@checkReadStatusProperty']);
            $router->post('/profile/notifications-email', ['uses' => 'NotificationController@setEmailNotification']);
            $router->post('/profile/unset-notifications-email', ['uses' => 'NotificationController@removeEmailNotification']);
        });
        # NOTIFICATION END

        # PROFILE START
        $router->group(['prefix' => 'profile',  'namespace' => 'Profile'], function () use ($router) {
            #read
            $router->post('/info', ['uses' => 'ProfileController@profileMainInfo']);

            $router->get('/image/{user_id}', ['uses' => 'ProfileController@getProfileImage']);

            $router->post('/count-followers', ['uses' => 'ProfileController@countFollowers']);
            $router->post('/count-following', ['uses' => 'ProfileController@countFollowing']);
            $router->post('/count-connections', ['uses' => 'ProfileController@countConnections']);
            $router->post('/reputation-score', ['uses' => 'ProfileController@getReputationScore']);
            $router->post('/activity-score', ['uses' => 'ProfileController@getActivityScore']);

            $router->post('/social-connect', ['uses' => 'ProfileController@getSocialConnected']);
            $router->post('/social-connect-history', ['uses' => 'ProfileController@getSocialConnectHistory']);
            $router->post('/social-connect-status', ['uses' => 'ProfileController@getSocialConnectionStatus']);
            $router->post('/social-fb-profile-picture',['uses' => 'ProfileController@getFbProfilePictures']);
            $router->post('/all-followers', ['uses' => 'ProfileController@allFollowers']);
            $router->post('/all-following', ['uses' => 'ProfileController@allFollowing']);
            $router->post('/all-connections', ['uses' => 'ProfileController@allConnections']);
            $router->post('/all-blocked', ['uses' => 'ProfileController@allBlockedUsers']);
            $router->post('/login-history', ['uses' => 'ProfileController@getLoginHistory']);
            $router->post('/login-history-search', ['uses' => 'ProfileController@searchLoginHistory']);
            $router->post('/timeline', ['uses' => 'ProfileController@getTimeline']);
            $router->post('/generate-steemit-footer', ['uses' => 'ProfileController@generateSteemitFooter']);
            $router->post('/active-task', ['uses' => 'ProfileController@profileTaskActive']);
            $router->post('/user-verification', ['uses' => 'ProfileController@userVerificationList']);
            $router->post('/check-is-follower',['uses'=> 'ProfileController@checkIsFollower']);

            #write
            $router->post('/update-account', ['uses' => 'ProfileController@updateAccount']);
            $router->post('/update-password', ['uses' => 'ProfileController@updatePassword']);
            $router->post('/update-profile-image', ['uses' => 'ProfileController@updateProfileImage']);
            $router->post('/save-selfie', ['uses' => 'ProfileController@saveSelfie']);
            $router->post('/get-verified', ['uses' => 'ProfileController@getVerified']);
            $router->post('/resend-verification', ['uses' => 'ProfileController@resendVerification']);
            $router->post('/toggle-block-user', ['uses' => 'ProfileController@toggleBlockUsers']);
            $router->post('/toggle-follow-user', ['uses' => 'ProfileController@toggleFollowUsers']);
            $router->post('/toggle-social-link', ['uses' => 'ProfileController@toggleSocialLink']);
            $router->post('/gift-superior-coin', ['uses' => 'ProfileController@giftSuperiorCoin']);
            $router->post('/save-referrer', ['uses' => 'ProfileController@saveOwnReferrer']);

            $router->post('/unblock-user', ['uses' => 'ProfileController@unblockUser']);

            # USER TALENT PROFILE
            $router->post('/talent/index', ['uses' => 'TalentProfileController@index']);
            $router->post('/talent/create', ['uses' => 'TalentProfileController@create']);
            $router->post('/talent/update', ['uses' => 'TalentProfileController@update']);
            $router->post('/talent/toggle-status', ['uses' => 'TalentProfileController@toggleStatus']);

            
        });
        # PROFILE END

        # SOCIAL CONNECT START
        $router->group(['prefix' => 'social-connect',  'namespace' => 'Social'], function () use ($router) {
            #write
            $router->post('/hard-unlink-request', ['uses' => 'SocialController@hardUnlinkRequest']);
            $router->post('/hard-unlink-denied-request', ['uses' => 'SocialController@deniedHardUnlinkRequest']);
            $router->post('/hard-unlink', ['uses' => 'SocialController@hardUnlink']);
        });
        # SOCIAL CONNECT END

        # ANNOUNCEMENT START
        $router->group(['prefix' => 'announcement',  'namespace' => 'Announcement'], function () use ($router) {
            #read
            $router->post('/', ['uses' => 'AnnouncementController@index']);

            #write
            $router->post('/submit-request', ['uses' => 'AnnouncementController@submitRequest']);

        });
        # ANNOUNCEMENT END

        # VOTE START
          $router->group(['prefix' => 'vote',  'namespace' => 'Vote'], function () use ($router) {
            #read
            $router->post('/voting-poll-list', ['uses' => 'VotingController@getVotingPollList']);
            $router->post('/voting-poll-details', ['uses' => 'VotingController@getVotingPollDetails']);
            $router->post('/vote',['uses' => 'VotingController@voteRequest']);

        });
        # VOTE END

         # CHAT START
         $router->group(['prefix' => 'chat',  'namespace' => 'Chat'], function () use ($router) {
            #read
            $router->get('/get-users-list', ['uses' => 'ChatController@getChatUsersList']);
            $router->post('/get-user-chat-list', ['uses' => 'ChatController@getUserChatList']);
            $router->get('/get-all-private-chat/{user_id}', ['uses' => 'ChatController@getAllPrivate']);
            $router->get('/get-all-group-chat/{group_id}', ['uses' => 'ChatController@getAllGroup']);
            $router->get('/get-emoji/{type}', ['uses' => 'ChatController@getEmojis']);

            $router->post('/send-to-private-chat', ['uses' => 'ChatController@sendToPrivate']);
            $router->post('/send-to-group-chat', ['uses' => 'ChatController@sendToGroup']);
            $router->post('/create-group-chat', ['uses' => 'ChatController@createGroupChat']);
            $router->post('/update-group-chat', ['uses' => 'ChatController@updateGroupChat']);
            $router->post('/leave-group-chat', ['uses' => 'ChatController@leaveGroupChat']);
            $router->post('/save-to-redis', ['uses' => 'ChatController@saveToRedis']);
            $router->post('/delete-to-redis', ['uses' => 'ChatController@deleteFromRedis']);

        });
        # CHAT END

        # LANDING START

        # LANDING END

        $router->group(['prefix' => 'debug'], function () use ($router) {
            
        });

    });

    $router->group(['prefix' => 'landing'], function () use ($router) {


        $router->group(['prefix' => 'blog', 'namespace' => 'Blog'], function () use ($router) {
            $router->post('/get-featured-bloggers', ['uses' => 'BlogController@getTopBloggers']);
        });

        $router->group(['prefix' => 'task', 'namespace' => 'Task'], function () use ($router) {
            $router->post('/get-featured-task-creator', ['uses' => 'TaskController@getFeaturedTaskCreator']);
        });

        $router->group(['prefix' => 'user', 'namespace' => 'User'], function () use ($router) {
            $router->post('/newly-registered-counter', ['uses' => 'UserController@getNewlyRegisteredCounter']);
        });

        $router->group(['prefix' => 'roadmap'], function () use ($router) {
            $router->post('/', ['uses' => 'RoadmapController@index']);
            $router->post('/add', ['uses' => 'RoadmapController@createRoadmap']);
            $router->post('/update/{id}', ['uses' => 'RoadmapController@updateRoadmap']);
            $router->post('/delete/{id}', ['uses' => 'RoadmapController@deleteRoadmap']);
        });

        $router->group(['prefix' => 'sites'], function () use ($router) {
            $router->post('/', ['uses' => 'LandingController@getSites']);
            $router->post('/add', ['uses' => 'LandingController@addSites']);
            $router->post('/update/{id}', ['uses' => 'LandingController@updateSite']);
            $router->post('/delete/{id}', ['uses' => 'LandingController@deleteSite']);
        });

        
        $router->group(['prefix' => 'business'], function () use ($router) {
            $router->post('/', ['uses' => 'LandingController@getBusiness']);
            $router->post('/add', ['uses' => 'LandingController@addBusiness']);;
        });

        $router->group(['prefix' => 'testimonials'], function () use ($router) {
            $router->post('/', ['uses' => 'LandingController@getTestimonials']);
        });

    });
});
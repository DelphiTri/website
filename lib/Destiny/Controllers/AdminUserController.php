<?php
namespace Destiny\Controllers;

use Destiny\Chat\ChatBanService;
use Destiny\Chat\ChatRedisService;
use Destiny\Common\Application;
use Destiny\Common\Log;
use Destiny\Common\Utils\Date;
use Destiny\Common\Exception;
use Destiny\Common\ViewModel;
use Destiny\Common\Utils\Country;
use Destiny\Common\Annotation\Controller;
use Destiny\Common\Annotation\Route;
use Destiny\Common\Annotation\HttpMethod;
use Destiny\Common\Annotation\Secure;
use Destiny\Common\User\UserService;
use Destiny\Common\Authentication\AuthenticationService;
use Destiny\Common\Session\Session;
use Destiny\Commerce\SubscriptionsService;
use Destiny\Common\Utils\FilterParams;
use Destiny\Common\Config;
use Destiny\Commerce\OrdersService;
use Doctrine\DBAL\DBALException;

/**
 * @Controller
 */
class AdminUserController {

    /**
     * Get only roles that your security level allows for you to
     * apply to other users.
     * @throws DBALException
     */
    private function getAllowedRoles() {
        $userService = UserService::instance();
        $roles = $userService->getAllRoles();
        $exclude = ['USER','SUBSCRIBER'];
        return array_filter($roles, function($v) use ($exclude) {
            return !in_array($v['roleName'], $exclude);
        });
    }

    /**
     * @Route ("/admin/user/{id}/edit")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"GET"})
     *
     * @param array $params
     * @param ViewModel $model
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function adminUserEdit(array $params, ViewModel $model) {
        FilterParams::required($params, 'id');
        $user = UserService::instance()->getUserById($params ['id']);
        if (empty ($user)) {
            throw new Exception ('User was not found');
        }

        $userService = UserService::instance();
        $chatBanService = ChatBanService::instance();
        $redisService = ChatRedisService::instance();
        $subscriptionsService = SubscriptionsService::instance();

        $user ['roles'] = $userService->getRolesByUserId($user ['userId']);
        $user ['features'] = $userService->getFeaturesByUserId($user ['userId']);
        $user ['ips'] = $redisService->getIPByUserId($user ['userId']);

        $model->user = $user;
        $model->smurfs = $userService->getUsersByUserIds($redisService->findUserIdsByUsersIp($user ['userId']));
        $model->features = $userService->getAllFeatures();
        $model->roles = $this->getAllowedRoles();
        $model->ban = $chatBanService->getUserActiveBan($user ['userId']);
        $model->authSessions = $userService->getAuthByUserId($user ['userId']);
        $model->subscriptions = $subscriptionsService->findByUserId($user ['userId']);
        $model->gifts = $subscriptionsService->findCompletedByGifterId($user ['userId']);

        $gifters = [];
        $recipients = [];

        foreach ($model->subscriptions as $subscription) {
            if (!empty($subscription['gifter'])) {
                $gifters[$subscription['gifter']] = $userService->getUserById($subscription['gifter']);
            }
        }
        foreach ($model->gifts as $subscription) {
            $recipients[$subscription['userId']] = $userService->getUserById($subscription['userId']);
        }

        $model->gifters = $gifters;
        $model->recipients = $recipients;
        $model->title = 'User';
        return 'admin/user';
    }

    /**
     * @Route ("/admin/user/{id}/edit")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"POST"})
     *
     * @param array $params
     * @return string
     *
     * @throws DBALException
     * @throws Exception
     */
    public function adminUserEditProcess(array $params) {
        FilterParams::required($params, 'id');
        $authService = AuthenticationService::instance();
        $userService = UserService::instance();

        $user = $userService->getUserById($params ['id']);
        if (empty ($user)) {
            throw new Exception ('User was not found');
        }

        $redirect = 'redirect: /admin/user/' . $user ['userId'] . '/edit';
        $username = (isset ($params ['username']) && !empty ($params ['username'])) ? $params ['username'] : $user ['username'];
        $email = (isset ($params ['email']) && !empty ($params ['email'])) ? $params ['email'] : $user ['email'];
        $country = (isset ($params ['country']) && !empty ($params ['country'])) ? $params ['country'] : $user ['country'];
        $allowGifting = (isset ($params ['allowGifting'])) ? $params ['allowGifting'] : $user ['allowGifting'];
        $istwitchsubscriber = (isset ($params ['istwitchsubscriber'])) ? $params ['istwitchsubscriber'] : $user ['istwitchsubscriber'];
        $discordname = (isset ($params ['discordname'])) ? $params ['discordname'] : $user ['discordname'];
        $discorduuid = (isset ($params ['discorduuid'])) ? $params ['discorduuid'] : $user ['discorduuid'];;
        $minecraftname = (isset ($params ['minecraftname'])) ? $params ['minecraftname'] : $user ['minecraftname'];
        $minecraftuuid = (isset ($params ['minecraftuuid'])) ? $params ['minecraftuuid'] : $user ['minecraftuuid'];

        if (empty($minecraftname))
            $minecraftname = null;
        else if (mb_strlen($minecraftname) > 16)
            $minecraftname = mb_substr($minecraftname, 0, 16);

        if (empty($minecraftuuid))
            $minecraftuuid = null;
        else if (mb_strlen($minecraftuuid) > 36)
            $minecraftuuid = mb_substr($minecraftuuid, 0, 36);

        if (empty($discordname))
            $discordname = null;
        else if (mb_strlen($discordname) > 36)
            $discordname = mb_substr($discordname, 0, 36);

        if (empty($discorduuid))
            $discorduuid = null;
        else if (mb_strlen($discorduuid) > 36)
            $discorduuid = mb_substr($discorduuid, 0, 36);

        if (!empty($email)) {
            $authService->validateEmail($email, $user);
        }

        if (!empty ($country)) {
            $countryArr = Country::getCountryByCode($country);
            $country = $countryArr ['alpha-2'];
        }

        $mUid = $userService->getUserIdByField('minecraftname', $params['minecraftname']);
        if($minecraftname != null && !empty($mUid) && intval($mUid) !== intval($user ['userId'])) {
            Session::setErrorBag('Minecraft name already in use #');
            return $redirect;
        }

        $dUid = $userService->getUserIdByField('discordname', $params['discordname']);
        if ($discordname != null && !empty($dUid) && intval($dUid) !== intval($user ['userId'])) {
            Session::setErrorBag('Discord name already in use #' . $dUid);
            return $redirect;
        }

        $userData = [
            'username' => $username,
            'country' => $country,
            'email' => $email,
            'allowGifting' => $allowGifting,
            'istwitchsubscriber' => $istwitchsubscriber,
            'discordname' => $discordname,
            'discorduuid' => $discorduuid,
            'minecraftname' => $minecraftname,
            'minecraftuuid' => $minecraftuuid,
        ];

        $conn = Application::getDbConn();
        try {
            $conn->beginTransaction();
            $userService->updateUser($user ['userId'], $userData);
            $user = $userService->getUserById($params ['id']);
            $authService->flagUserForUpdate($user ['userId']);
            $conn->commit();
        } catch (DBALException $e) {
            Log::critical("Error updating user", $user);
            $conn->rollBack();
            throw $e;
        }

        Session::setSuccessBag('User profile updated');
        return $redirect;
    }

    /**
     * @Route ("/admin/user/{id}/toggle/flair")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"POST"})
     *
     * @param array $params
     *
     * @throws DBALException
     * @throws Exception
     */
    public function toggleUserFlair(array $params) {
        FilterParams::required($params, 'userId');
        FilterParams::declared($params, 'value');
        FilterParams::required($params, 'name');
        $userService = UserService::instance();
        $user = $userService->getUserById($params ['userId']);
        if (empty ($user)) {
            throw new Exception ('User was not found');
        }
        $userService->removeUserFeature($user['userId'], $params['name']);
        if (intval($params['value']) == 1) {
            $userService->addUserFeature($user['userId'], $params['name']);
        }
        AuthenticationService::instance()->flagUserForUpdate($user ['userId']);
    }

    /**
     * @Route ("/admin/user/{id}/toggle/role")
     * @Secure ({"ADMIN"})
     * @HttpMethod ({"POST"})
     *
     * @param array $params
     *
     * @throws DBALException
     * @throws Exception
     */
    public function toggleUserRole(array $params) {
        FilterParams::required($params, 'userId');
        FilterParams::declared($params, 'value');
        FilterParams::required($params, 'name');
        $userService = UserService::instance();
        $user = $userService->getUserById($params['userId']);
        if (empty ($user)) {
            throw new Exception ('User was not found');
        }
        $userService->removeUserRole($user['userId'], $params['name']);
        if (intval($params['value']) == 1) {
            $userService->addUserRole($user['userId'], $params['name']);
        }
        AuthenticationService::instance()->flagUserForUpdate($user['userId']);
    }

    /**
     * @Route ("/admin/user/{id}/subscription/add")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"GET"})
     *
     * @param array $params
     * @param ViewModel $model
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function subscriptionAdd(array $params, ViewModel $model) {
        FilterParams::required($params, 'id');
        $userService = UserService::instance();
        $model->user = $userService->getUserById($params ['id']);
        $model->subscriptions = Config::$a ['commerce'] ['subscriptions'];
        $model->subscription = [
            'subscriptionType' => '',
            'createdDate' => gmdate('Y-m-d H:i:s'),
            'endDate' => gmdate('Y-m-d H:i:s'),
            'status' => 'Active',
            'gifter' => '',
            'recurring' => false
        ];
        $authService = AuthenticationService::instance();
        $authService->flagUserForUpdate($params ['id']);
        $model->title = 'Subscription';
        return "admin/subscription";
    }

    /**
     * @Route ("/admin/user/{id}/subscription/{subscriptionId}/edit")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"GET"})
     *
     * @param array $params
     * @param ViewModel $model
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function subscriptionEdit(array $params, ViewModel $model) {
        FilterParams::required($params, 'id');
        FilterParams::required($params, 'subscriptionId');

        $subscriptionsService = SubscriptionsService::instance();
        $userService = UserService::instance();
        $ordersService = OrdersService::instance();

        $subscription = [];
        $payments = [];

        if (!empty ($params ['subscriptionId'])) {
            $subscription = $subscriptionsService->findById($params ['subscriptionId']);
            $payments = $ordersService->getPaymentsBySubscriptionId($subscription ['subscriptionId']);
        }

        $model->user = $userService->getUserById($params ['id']);
        $model->subscriptions = Config::$a ['commerce'] ['subscriptions'];
        $model->subscription = $subscription;
        $model->payments = $payments;
        $model->title = 'Subscription';
        return "admin/subscription";
    }

    /**
     * @Route ("/admin/user/{id}/subscription/{subscriptionId}/save")
     * @Route ("/admin/user/{id}/subscription/save")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"POST"})
     *
     * @param array $params
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function subscriptionSave(array $params) {
        FilterParams::required($params, 'subscriptionType');
        FilterParams::required($params, 'status');
        FilterParams::required($params, 'createdDate');
        FilterParams::required($params, 'endDate');
        FilterParams::declared($params, 'gifter');

        $userService = UserService::instance();
        $subscriptionsService = SubscriptionsService::instance();
        $subscriptionType = $subscriptionsService->getSubscriptionType($params ['subscriptionType']);

        $subscription = [];
        $subscription ['subscriptionType'] = $subscriptionType ['id'];
        $subscription ['subscriptionTier'] = $subscriptionType ['tier'];
        $subscription ['status'] = $params ['status'];
        $subscription ['createdDate'] = $params ['createdDate'];
        $subscription ['endDate'] = $params ['endDate'];
        $subscription ['userId'] = $params ['id'];
        $subscription ['subscriptionSource'] = (isset ($params ['subscriptionSource']) && !empty ($params ['subscriptionSource'])) ? $params ['subscriptionSource'] : Config::$a ['subscriptionType'];

        if (!empty($params ['gifter'])) {
            if (!is_numeric($params ['gifter'])) {
                $gifter = $userService->getUserByUsername($params['gifter']);
                if (empty($gifter))
                    throw new Exception ('Invalid giftee (user not found)');
                if ($subscription ['userId'] == $gifter['userId'])
                    throw new Exception ('Invalid giftee (cannot gift yourself)');
                $subscription ['gifter'] = $gifter['userId'];
            } else {
                $subscription ['gifter'] = $params['gifter'];
            }
        }

        if (isset ($params ['subscriptionId']) && !empty ($params ['subscriptionId'])) {
            $subscription ['subscriptionId'] = $params ['subscriptionId'];
            $subscriptionId = $subscription ['subscriptionId'];
            $subscriptionsService->updateSubscription($subscription);
            Session::setSuccessBag('Subscription updated!');
        } else {
            $subscriptionId = $subscriptionsService->addSubscription($subscription);
            Session::setSuccessBag('Subscription created!');
        }


        $authService = AuthenticationService::instance();
        $authService->flagUserForUpdate($params ['id']);

        return 'redirect: /admin/user/' . urlencode($params['id']) . '/subscription/' . urlencode($subscriptionId) . '/edit';
    }

    /**
     * @Route ("/admin/user/{id}/auth/{provider}/delete")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"POST"})
     *
     * @param array $params
     * @return string
     *
     * @throws DBALException
     */
    public function authProviderDelete(array $params) {
        $userService = UserService::instance();
        $userService->removeAuthProfile($params['id'], $params['provider']);
        Session::setSuccessBag('Authentication profile removed!');
        return 'redirect: /admin/user/' . urlencode($params['id']) . '/edit';
    }

    /**
     * @Route ("/admin/user/{userId}/ban")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"GET"})
     *
     * @param array $params
     * @param ViewModel $model
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function addBan(array $params, ViewModel $model) {
        $model->title = 'New Ban';
        if (!isset ($params ['userId']) || empty ($params ['userId'])) {
            throw new Exception ('userId required');
        }

        $userService = UserService::instance();
        $user = $userService->getUserById($params ['userId']);
        if (empty ($user)) {
            throw new Exception ('User was not found');
        }

        $model->user = $user;
        $time = Date::getDateTime('NOW');
        $model->ban = [
            'reason' => '',
            'starttimestamp' => $time->format('Y-m-d H:i:s'),
            'endtimestamp' => ''
        ];
        return 'admin/userban';
    }

    /**
     * @Route ("/admin/user/{userId}/ban")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"POST"})
     *
     * @param array $params
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function insertBan(array $params) {
        if (!isset ($params ['userId']) || empty ($params ['userId'])) {
            throw new Exception ('userId required');
        }
        $ban = [];
        $ban ['reason'] = $params ['reason'];
        $ban ['userid'] = Session::getCredentials()->getUserId();
        $ban ['ipaddress'] = '';
        $ban ['targetuserid'] = $params ['userId'];
        $ban ['starttimestamp'] = Date::getDateTime($params ['starttimestamp'])->format('Y-m-d H:i:s');
        if (!empty ($params ['endtimestamp'])) {
            $ban ['endtimestamp'] = Date::getDateTime($params ['endtimestamp'])->format('Y-m-d H:i:s');
        }
        $chatBanService = ChatBanService::instance();
        $ban ['id'] = $chatBanService->insertBan($ban);
        AuthenticationService::instance()->flagUserForUpdate($ban ['targetuserid']);
        return 'redirect: /admin/user/' . $params ['userId'] . '/ban/' . $ban ['id'] . '/edit';
    }

    /**
     * @Route ("/admin/user/{userId}/ban/{id}/edit")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"GET"})
     *
     * @param array $params
     * @param ViewModel $model
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function editBan(array $params, ViewModel $model) {
        $model->title = 'Update Ban';
        if (!isset ($params ['id']) || empty ($params ['id'])) {
            throw new Exception ('id required');
        }
        if (!isset ($params ['userId']) || empty ($params ['userId'])) {
            throw new Exception ('userId required');
        }

        $userService = UserService::instance();
        $chatBanService = ChatBanService::instance();
        $user = $userService->getUserById($params ['userId']);
        if (empty ($user)) {
            throw new Exception ('User was not found');
        }

        $model->user = $user;
        $model->ban = $chatBanService->getBanById($params ['id']);
        return 'admin/userban';
    }

    /**
     * @Route ("/admin/user/{userId}/ban/{id}/update")
     * @Secure ({"MODERATOR"})
     * @HttpMethod ({"POST"})
     *
     * @param array $params
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function updateBan(array $params) {
        if (!isset ($params ['id']) || empty ($params ['id'])) {
            throw new Exception ('id required');
        }
        if (!isset ($params ['userId']) || empty ($params ['userId'])) {
            throw new Exception ('userId required');
        }

        $chatBanService = ChatBanService::instance();
        $authService = AuthenticationService::instance();
        $eBan = $chatBanService->getBanById($params ['id']);

        $ban = [];
        $ban ['id'] = $eBan ['id'];
        $ban ['reason'] = $params ['reason'];
        $ban ['userid'] = $eBan ['userid'];
        $ban ['ipaddress'] = $eBan ['ipaddress'];
        $ban ['targetuserid'] = $eBan ['targetuserid'];
        $ban ['starttimestamp'] = Date::getDateTime($params ['starttimestamp'])->format('Y-m-d H:i:s');
        $ban ['endtimestamp'] = '';
        if (!empty ($params ['endtimestamp'])) {
            $ban ['endtimestamp'] = Date::getDateTime($params ['endtimestamp'])->format('Y-m-d H:i:s');
        }
        $chatBanService->updateBan($ban);
        $authService->flagUserForUpdate($ban ['targetuserid']);

        return 'redirect: /admin/user/' . $params ['userId'] . '/ban/' . $params ['id'] . '/edit';
    }

    /**
     * @Route ("/admin/user/{userId}/ban/remove")
     * @Secure ({"MODERATOR"})
     *
     * @param array $params
     * @return string
     *
     * @throws Exception
     * @throws DBALException
     */
    public function removeBan(array $params) {
        if (!isset ($params ['userId']) || empty ($params ['userId'])) {
            throw new Exception ('userId required');
        }

        $chatBanService = ChatBanService::instance();
        $authService = AuthenticationService::instance();

        // if there were rows modified there were bans removed, so an update is
        // required, removeUserBan returns the number of rows modified
        if ($chatBanService->removeUserBan($params ['userId']))
            $authService->flagUserForUpdate($params ['userId']);

        if (isset($params['follow']) and substr($params['follow'], 0, 1) == '/')
            return 'redirect: ' . $params['follow'];

        return 'redirect: /admin/user/' . $params ['userId'] . '/edit';
    }

}

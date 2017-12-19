<?php

namespace Charcoal\Admin\Action\Account;

use Exception;

// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Intra-module (`charcoal-admin`) dependencies
use Charcoal\Admin\AdminAction;
use Charcoal\Admin\User;
use Charcoal\Admin\User\LostPasswordToken;

/**
 * Reset Password Action
 *
 * This action is used to process a user's new password given a valid
 * _password reset token_ generared by
 * {@see \Charcoal\Admin\Action\Account\LostPasswordAction}.
 *
 * ## Required Parameters
 *
 * - `token`
 * - `username`
 * - `password1`
 * - `password2`
 * - `g-recaptcha-response`
 *
 * ## HTTP Status Codes
 *
 * - `200` — Successful; Password has been changed
 * - `400` — Client error; Invalid request data
 * - `500` — Server error; Password could not be changed
 */
class ResetPasswordAction extends AdminAction
{
    /**
     * @return boolean
     */
    public function authRequired()
    {
        return false;
    }

    /**
     * Note that the lost-password action should never change status code and always return 200.
     *
     * @todo   This should be done via an Authenticator object.
     * @todo   Implement "sendResetPasswordEmail"
     * @param  RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param  ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

        $token     = $request->getParam('token');
        $username  = $request->getParam('username');
        $password1 = $request->getParam('password1');
        $password2 = $request->getParam('password2');

        if (!$token) {
            $this->addFeedback('error', $this->translator()->translate('Missing reset token.'));
            $this->setSuccess(false);

            return $response->withStatus(400);
        }

        if (!$username) {
            $this->addFeedback('error', $this->translator()->translate('Missing username.'));
            $this->setSuccess(false);

            return $response->withStatus(400);
        }

        if (!$password1) {
            $this->addFeedback('error', $this->translator()->translate('Missing password'));
            $this->setSuccess(false);

            return $response->withStatus(400);
        }

        if (!$password2) {
            $this->addFeedback('error', $this->translator()->translate('Missing password confirmation'));
            $this->setSuccess(false);

            return $response->withStatus(400);
        }

        if ($password1 != $password2) {
            $this->addFeedback('error', $this->translator()->translate('Passwords do not match'));
            $this->setSuccess(false);

            return $response->withStatus(400);
        }

        $recaptchaValue = $request->getParam('g-recaptcha-response');
        if (!$recaptchaValue) {
            $this->addFeedback('error', $this->translator()->translate('Missing CAPTCHA response.'));
            $this->setSuccess(false);

            return $response->withStatus(400);
        }

        if (!$this->validateCaptcha($recaptchaValue)) {
            $this->addFeedback('error', $this->translator()->translate('Invalid or malformed CAPTCHA response.'));
            $this->setSuccess(false);

            return $response->withStatus(400);
        }

        $failMessage = $this->translator()->translation('An error occurred while processing the password change.');

        $user = $this->loadUser($username);
        if ($user === null) {
            if ($ip) {
                $logMessage = sprintf(
                    '[Admin] Reset Password — Can not find "%s" user in database for %s.',
                    $username,
                    $ip
                );
            } else {
                $logMessage = sprintf(
                    '[Admin] Reset Password — Can not find "%s" user in database.',
                    $username
                );
            }
            $this->logger->error($logMessage);

            $this->addFeedback('error', $failMessage);
            $this->setSuccess(false);

            return $response->withStatus(500);
        }

        if (!$this->validateToken($token, $user->id())) {
            $this->addFeedback('error', $this->translator()->translate('Invalid or expired reset token.'));
            $this->setSuccess(false);

            return $response->withStatus(400);
        }

        try {
            $user->resetPassword($password1);
            $this->deleteToken($token);

            $this->addFeedback('success', $this->translator()->translate('Password has been successfully changed.'));
            $this->setSuccess(true);

            return $response;
        } catch (Exception $e) {
            if ($ip) {
                $logMessage = sprintf(
                    '[Admin] Reset Password — Failed to process change for "%s" from %s: %s',
                    $username,
                    $ip,
                    $e->getMessage()
                );
            } else {
                $logMessage = sprintf(
                    '[Admin] Reset Password — Failed to process change for "%s": %s',
                    $username,
                    $e->getMessage()
                );
            }
            $this->logger->error($logMessage);

            $this->addFeedback('error', $failMessage);
            $this->setSuccess(false);

            return $response->withStatus(500);
        }

        return $response;
    }

    /**
     * @return array
     */
    public function results()
    {
        $ret = [
            'success'   => $this->success(),
            'feedbacks' => $this->feedbacks()
        ];

        return $ret;
    }

    /**
     * @param string $username Username or email.
     * @return User|false
     */
    private function loadUser($username)
    {
        if (!$username) {
            return false;
        }

        // Try to get user by username
        $user = $this->modelFactory()->create(User::class);
        $user->loadFrom('username', $username);
        if ($user->id()) {
            return $user;
        }

        // Try to get user by email
        $user->loadFrom('email', $username);
        if ($user->id()) {
            return $user;
        }

        return false;
    }

    /**
     * Validate the given password reset token.
     *
     * To be valid, a token should:
     *
     * - exist in the database
     * - not be expired
     * - match the given user
     *
     * @see    \Charcoal\Admin\Template\Account::validateToken()
     * @param  string $token    The token to validate.
     * @param  string $username The user that should match the token.
     * @return boolean
     */
    private function validateToken($token, $username)
    {
        $obj = $this->modelFactory()->create(LostPasswordToken::class);
        $sql = strtr('SELECT * FROM `%table` WHERE `token` = :token AND `user` = :username AND `expiry` > NOW()', [
            '%table' => $obj->source()->table()
        ]);
        $obj->loadFromQuery($sql, [
            'token'    => $token,
            'username' => $username
        ]);

        return !!$obj->token();
    }

    /**
     * Delete the given password reset token.
     *
     * @param  string $token The token to delete.
     * @return void
     */
    private function deleteToken($token)
    {
        $obj = $this->modelFactory()->create(LostPasswordToken::class);
        $obj->setToken($token);
        $obj->delete();
    }
}

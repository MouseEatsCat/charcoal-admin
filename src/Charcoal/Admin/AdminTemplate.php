<?php

namespace Charcoal\Admin;

use \ArrayIterator;
use \Exception;
use \InvalidArgumentException;

// PSR-7 (HTTP Messaging) dependencies
use \Psr\Http\Message\RequestInterface;

use \Pimple\Container;

// Module `charcoal-factory` dependencies
use \Charcoal\Factory\FactoryInterface;

use \Charcoal\App\Template\AbstractTemplate;
use \Charcoal\Translation\TranslationString;
use \Charcoal\Admin\User;
use \Charcoal\Admin\Object\AuthToken;

/**
 * Base class for all `admin` Templates.
 *
 * An action extends
 *
 * # Available (mustache) methods
 * - `title` (TranslationString) - The page title
 * - `subtitle` (TranslationString) The page subtitle
 * - `showHeaderMenu` (bool) - Display the header menu or not
 * - `headerMenu` (iterator) - The header menu data
 * - `showFooterMenu` (bool) - Display the footer menu or not
 * - `footerMenu` (iterator) - The footer menu data
 * - `hasFeedback` (bool) - If there is feedback to display or not
 * - `feedback` (iterator) - The feedback data
 */
class AdminTemplate extends AbstractTemplate
{

    /**
     * Admin configuration
     * from main config['admin']
     * @var array
     */
    protected $adminConfig;

    /**
     * @var string $ident
     */
    private $ident;
    /**
     * @var TranslationString $label
     */
    private $label;

    /**
     * @var TranslationString $title
     */
    private $title;
    /**
     * @var TranslationString $subtitle
     */
    private $subtitle;

    /**
     * @var boolean $showHeaderMenu
     */
    private $showHeaderMenu = true;
    /**
     * @var boolean $showFooterMenu
     */
    private $showFooterMenu = true;

    /**
     * @var array $feedbacks
     */
    private $feedbacks;

    /**
     * @var \Charcoal\App\App $app
     */
    private $app;

    /**
     * @var FactoryInterface $modelFactory
     */
    private $modelFactory;

    /**
     * @return \Charcoal\App\App
     */
    protected function app()
    {
        if ($this->app === null) {
            $this->logger->notice('App from singleton');
            $this->app = \Charcoal\App\App::instance();
        }
        return $this->app;
    }

    /**
     * @param RequestInterface $request The request to initialize.
     * @return boolean
     */
    public function init(RequestInterface $request)
    {
        if (!session_id()) {
            session_cache_limiter(false);
            session_start();
        }

        if ($this->authRequired() !== false) {
            $this->auth();
        }

        // Initialize data with GET
        $this->setData($request->getParams());

        return parent::init($request);
    }

    /**
     * Dependencies
     * @param Container $container DI Container.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        $this->adminConfig = $container['charcoal/admin/config'];
        $this->setModelFactory($container['model/factory']);
    }

    /**
     * @param FactoryInterface $factory The factory used to create models.
     * @return AdminScript Chainable
     */
    protected function setModelFactory(FactoryInterface $factory)
    {
        $this->modelFactory = $factory;
        return $this;
    }

    /**
     * @return FactoryInterface The model factory.
     */
    protected function modelFactory()
    {
        if (!$this->modelFactory) {
            throw new Exception(
                sprintf('Model factory is not set for template "%s".', get_class($this))
            );
        }
        return $this->modelFactory;
    }

    /**
     * @param mixed $ident Template identifier.
     * @return AdminTemplate Chainable
     */
    public function setIdent($ident)
    {
        $this->ident = $ident;
        return $this;
    }

    /**
     * @return string
     */
    public function ident()
    {
        return $this->ident;
    }

    /**
     * @param mixed $label Template label.
     * @return AdminTemplate Chainable
     */
    public function setLabel($label)
    {
        $this->label = new TranslationString($label);
        return $this;
    }

    /**
     * @return TranslationString
     */
    public function label()
    {
        return $this->label;
    }

    /**
     * @param mixed $title Template title.
     * @return AdminTemplate Chainable
     */
    public function setTitle($title)
    {
        $this->title = new TranslationString($title);
        return $this;
    }

    /**
     * @return TranslationString
     */
    public function title()
    {
        if ($this->title === null) {
            $this->title = 'Undefined Title';
        }
        return $this->title;
    }

    /**
     * @param mixed $subtitle Template subtitle.
     * @return AdminTemplate Chainable
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = new TranslationString($subtitle);
        return $this;
    }

    /**
     * @return TranslationString
     */
    public function subtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param boolean $show The show header menu flag.
     * @return AdminTemplate Chainable
     */
    public function setShowHeaderMenu($show)
    {
        $this->showHeaderMenu = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showHeaderMenu()
    {
        return ($this->isAuthenticated() && $this->showHeaderMenu);
    }

    /**
     * @throws Exception If the menu was not properly configured.
     * @return void This method is a generator.
     */
    public function headerMenu()
    {
        $headerMenu = $this->adminConfig['header_menu'];

        if (!isset($headerMenu['items'])) {
            throw new Exception(
                'Header menu was not property configured.'
            );
        }

        foreach ($headerMenu['items'] as $menuItem) {
            if ($menuItem['url'] != '#') {
                $menuItem['url'] = $this->adminUrl().$menuItem['url'];
            }
            if (isset($menuItem['label'])) {
                $menuItem['label'] = new TranslationString($menuItem['label']);
            }
            yield $menuItem;
        }

    }

    /**
     * @param boolean $show The show footer menu flag.
     * @return AdminTemplate Chainable
     */
    public function setShowFooterMenu($show)
    {
        $this->showFooterMenu = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showFooterMenu()
    {
        return ($this->isAuthenticated() && $this->showFooterMenu);
    }

    /**
     * @return array
     */
    public function footerMenu()
    {
        return [];
    }

    /**
     * @return boolean
     */
    public function hasFeedbacks()
    {
        return (count($this->feedbacks()) > 0);
    }

    /**
     * @return array
     */
    public function feedbacks()
    {
        return $this->feedbacks;
    }

    /**
     * @param string $level The feedback level.
     * @param mixed  $msg   The feedback message.
     * @return AdminTemplate Chainable
     */
    public function addFeedback($level, $msg)
    {
        $this->feedbacks[] = [
            'msg'=>$msg,
            'level'=>$level
        ];
        return $this;
    }

    /**
     * Determine if user authentication is required.
     *
     * Authentication is required by default. If unnecessary,
     * replace this method in the inherited template class.
     *
     * For example, the "Login" / "Reset Password" templates
     * should return `FALSE`.
     *
     * @return boolean
     */
    protected function authRequired()
    {
        return true;
    }

    /**
     * Determine if the current user is authenticated.
     * If not it redirects them to the login page.
     *
     * @return void
     */
    private function auth()
    {
        if ($this->isAuthenticated()) {
            return;
        }

        // Not authenticated. Die.
        header('Location: '.$this->adminUrl().'login');
        exit;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return boolean
     */
    protected function isAuthenticated()
    {
        return ($this->authBySession() || $this->authByToken());
    }

    /**
     * @return boolean
     */
    private function authBySession()
    {
        $u = User::getAuthenticated($this->modelFactory());
        if ($u && $u->id()) {
            $u->saveToSession();
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return boolean
     */
    private function authByToken()
    {
        $authToken = $this->modelFactory->create('charcoal/admin/object/auth-token');

        if ($authToken->metadata()->enabled() !== true) {
            return false;
        }

        $cookieName = $authToken->metadata()->cookieName();

        if (!isset($_COOKIE[$cookieName])) {
            return false;
        }

        $authCookie = $_COOKIE[$cookieName];
        $vals = explode(';', $authCookie);
        $username = $authToken->getUserId($vals[0], $vals[1]);
        if (!$username) {
            return false;
        }

        $u = $this->modelFactory->create('charcoal/admin/user');

        $u->load($username);

        if ($u->id()) {
            $u->saveToSession();
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function adminUrl()
    {
        $adminPath = $this->adminConfig['base_path'];

        return rtrim($this->baseUrl(), '/').'/'.rtrim($adminPath, '/').'/';
    }

    /**
     * @return string
     */
    public function baseUrl()
    {
        $appConfig = $this->app()->config();

        if ($appConfig->has('URL')) {
            return $appConfig->get('URL');
        } else {
            $uri = $this->app()->getContainer()->get('request')->getUri();

            return rtrim($uri->getBaseUrl(), '/').'/';
        }
    }

    /**
     * @return string
     */
    public function headerMenuLogo()
    {
        if (!isset($this->adminConfig['menu_logo'])) {
            return 'assets/admin/images/user_01.jpg';
        }

        if (!is_string($this->adminConfig['menu_logo'])) {
            return 'assets/admin/images/user_01.jpg';
        }

        return $this->adminConfig['menu_logo'];
    }
}

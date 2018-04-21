<?php declare(strict_types=1);

namespace Tests\Functional\Core\Api\User;

use Brave\Core\Entity\PlayerRepository;
use Brave\Core\Roles;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Tests\Functional\WebTestCase;
use Tests\Helper;

class PlayerTest extends WebTestCase
{
    private $h;

    private $em;

    private $mid;

    private $player;

    private $group;

    private $pr;

    public function setUp()
    {
        $_SESSION = null;

        $this->h = new Helper();
        $this->em = $this->h->getEm();
        $this->pr = new PlayerRepository($this->em);
    }

    public function testAll403()
    {
        $response = $this->runApp('GET', '/api/user/player/all');
        $this->assertEquals(403, $response->getStatusCode());

        $this->setupDb();
        $this->loginUser(11); // not user-admin or group-admin

        $response = $this->runApp('GET', '/api/user/player/all');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAll200()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response = $this->runApp('GET', '/api/user/player/all');
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame([
            ['id' => $this->player->getId(), 'name' => 'Admin'],
            ['id' => $this->mid, 'name' => 'Manager'],
        ], $this->parseJsonBody($response));
    }

    public function testAddApplication403()
    {
        $response = $this->runApp('PUT', '/api/user/player/add-application/11');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAddApplication404()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response = $this->runApp('PUT', '/api/user/player/add-application/' . ($this->group->getId() + 1));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testAddApplication204()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response1 = $this->runApp('PUT', '/api/user/player/add-application/'. $this->group->getId());
        $response2 = $this->runApp('PUT', '/api/user/player/add-application/'. $this->group->getId());
        $this->assertEquals(204, $response1->getStatusCode());
        $this->assertEquals(204, $response2->getStatusCode());

        $this->em->clear();
        $p = $this->pr->find($this->player->getId());
        $this->assertSame(1, count($p->getApplications()));
    }

    public function testRemoveApplication403()
    {
        $response = $this->runApp('PUT', '/api/user/player/remove-application/11');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRemoveApplication404()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response = $this->runApp('PUT', '/api/user/player/remove-application/' . ($this->group->getId() + 1));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRemoveApplication204()
    {
        $this->setupDb();
        $this->loginUser(12);

        $this->player->addApplication($this->group);
        $this->em->flush();

        $response = $this->runApp('PUT', '/api/user/player/remove-application/' . $this->group->getId());
        $this->assertEquals(204, $response->getStatusCode());

        $this->em->clear();
        $p = $this->pr->find($this->player->getId());
        $this->assertSame(0, count($p->getApplications()));
    }

    public function testLeaveGroup403()
    {
        $response = $this->runApp('PUT', '/api/user/player/leave-group/11');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testLeaveGroup404()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response = $this->runApp('PUT', '/api/user/player/leave-group/' . ($this->group->getId() + 1));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testLeaveGroup204()
    {
        $this->setupDb();
        $this->loginUser(12);

        $this->player->addGroup($this->group);
        $this->em->flush();

        $response = $this->runApp('PUT', '/api/user/player/leave-group/' . $this->group->getId());
        $this->assertEquals(204, $response->getStatusCode());

        $this->em->clear();
        $p = $this->pr->find($this->player->getId());
        $this->assertSame(0, count($p->getGroups()));
    }

    public function testAppManagers403()
    {
        $response = $this->runApp('GET', '/api/user/player/app-managers');
        $this->assertEquals(403, $response->getStatusCode());

        $this->setupDb();
        $this->loginUser(11); // not user-admin or group-admin

        $response = $this->runApp('GET', '/api/user/player/app-managers');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAppManagers200NoGroup()
    {
        $h = new Helper();
        $h->emptyDb();
        $h->addCharacterMain('Admin', 12, [Roles::APP_ADMIN]);
        $this->loginUser(12);

        $log = new Logger('test');
        $log->pushHandler(new TestHandler());

        $response = $this->runApp('GET', '/api/user/player/app-managers', null, null, [
            LoggerInterface::class => $log
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame([], $this->parseJsonBody($response));
        $this->assertSame(
            'PlayerController->getManagers(): role "app-manager" not found.',
            $log->getHandlers()[0]->getRecords()[0]['message']
        );
    }

    public function testAppManagers200()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response = $this->runApp('GET', '/api/user/player/app-managers');
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame(
            [['id' => $this->mid, 'name' => 'Manager']],
            $this->parseJsonBody($response)
        );
    }

    public function testGroupManagers403()
    {
        $response = $this->runApp('GET', '/api/user/player/group-managers');
        $this->assertEquals(403, $response->getStatusCode());

        $this->setupDb();
        $this->loginUser(11); // not user-admin or group-admin

        $response = $this->runApp('GET', '/api/user/player/group-managers');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGroupManagers200NoGroup()
    {
        $h = new Helper();
        $h->emptyDb();
        $h->addCharacterMain('Admin', 12, [Roles::GROUP_ADMIN]);
        $this->loginUser(12);

        $log = new Logger('test');
        $log->pushHandler(new TestHandler());

        $response = $this->runApp('GET', '/api/user/player/group-managers', null, null, [
            LoggerInterface::class => $log
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame([], $this->parseJsonBody($response));
        $this->assertSame(
            'PlayerController->getManagers(): role "group-manager" not found.',
            $log->getHandlers()[0]->getRecords()[0]['message']
        );
    }

    public function testGroupManagers200()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response = $this->runApp('GET', '/api/user/player/group-managers');
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame(
            [['id' => $this->mid, 'name' => 'Manager']],
            $this->parseJsonBody($response)
        );
    }

    public function testRoles403()
    {
        $response = $this->runApp('GET', '/api/user/player/1/roles');
        $this->assertEquals(403, $response->getStatusCode());

        $this->setupDb();
        $this->loginUser(11); // not user-admin or group-admin

        $response = $this->runApp('GET', '/api/user/player/1/roles');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRoles404()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response = $this->runApp('GET', '/api/user/player/'.($this->player->getId() + 1).'/roles');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRoles200()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response = $this->runApp('GET', '/api/user/player/'.$this->player->getId().'/roles');
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame(
            [Roles::APP_ADMIN, Roles::GROUP_ADMIN, Roles::USER, Roles::USER_ADMIN],
            $this->parseJsonBody($response)
        );
    }

    public function testAddRole403()
    {
        $response = $this->runApp('PUT', '/api/user/player/101/add-role/r');
        $this->assertEquals(403, $response->getStatusCode());

        $this->setupDb();
        $this->loginUser(11); // not user-admin or group-admin

        $response = $this->runApp('PUT', '/api/user/player/101/add-role/r');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAddRole404()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response1 = $this->runApp('PUT', '/api/user/player/101/add-role/r');
        $response2 = $this->runApp('PUT', '/api/user/player/101/add-role/'.Roles::APP_MANAGER);
        $response3 = $this->runApp('PUT', '/api/user/player/'.$this->player->getId().'/add-role/role');

        // app is a valid role, but not for users
        $response4 = $this->runApp('PUT', '/api/user/player/'.$this->player->getId().'/add-role/'.Roles::APP);

        $this->assertEquals(404, $response1->getStatusCode());
        $this->assertEquals(404, $response2->getStatusCode());
        $this->assertEquals(404, $response3->getStatusCode());
        $this->assertEquals(404, $response4->getStatusCode());
    }

    public function testAddRole204()
    {
        $this->setupDb();
        $this->loginUser(12);

        $r1 = $this->runApp('PUT', '/api/user/player/'.($this->player->getId()).'/add-role/'.Roles::APP_MANAGER);
        $r2 = $this->runApp('PUT', '/api/user/player/'.($this->player->getId()).'/add-role/'.Roles::APP_MANAGER);
        $this->assertEquals(204, $r1->getStatusCode());
        $this->assertEquals(204, $r2->getStatusCode());

        $this->em->clear();

        $player = (new PlayerRepository($this->em))->find($this->player->getId());
        $this->assertSame(
            [Roles::APP_ADMIN, Roles::APP_MANAGER, Roles::GROUP_ADMIN, Roles::USER, Roles::USER_ADMIN],
            $player->getRoleNames()
        );
    }

    public function testRemoveRole403()
    {
        $response = $this->runApp('PUT', '/api/user/player/101/remove-role/r');
        $this->assertEquals(403, $response->getStatusCode());

        $this->setupDb();
        $this->loginUser(11); // not user-admin or group-admin

        $response = $this->runApp('PUT', '/api/user/player/101/remove-role/r');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRemoveRole404()
    {
        $this->setupDb();
        $this->loginUser(12);

        $response1 = $this->runApp('PUT', '/api/user/player/101/remove-role/a');
        $response2 = $this->runApp('PUT', '/api/user/player/101/remove-role/'.Roles::APP_MANAGER);
        $response3 = $this->runApp('PUT', '/api/user/player/'.$this->player->getId().'/remove-role/a');

        // user is a valid role, but may not be removed
        $response4 = $this->runApp('PUT', '/api/user/player/'.$this->player->getId().'/remove-role/'.Roles::USER);

        $this->assertEquals(404, $response1->getStatusCode());
        $this->assertEquals(404, $response2->getStatusCode());
        $this->assertEquals(404, $response3->getStatusCode());
        $this->assertEquals(404, $response4->getStatusCode());
    }

    public function testRemoveRole204()
    {
        $this->setupDb();
        $this->loginUser(12);

        $r1 = $this->runApp('PUT', '/api/user/player/'.$this->player->getId().'/remove-role/'.Roles::APP_ADMIN);
        $r2 = $this->runApp('PUT', '/api/user/player/'.$this->player->getId().'/remove-role/'.Roles::APP_ADMIN);
        $this->assertEquals(204, $r1->getStatusCode());
        $this->assertEquals(204, $r2->getStatusCode());

        $this->em->clear();

        $player = (new PlayerRepository($this->em))->find($this->player->getId());
        $this->assertSame(
            [Roles::GROUP_ADMIN, Roles::USER, Roles::USER_ADMIN],
            $player->getRoleNames()
        );
    }

    private function setupDb()
    {
        $this->h->emptyDb();

        $this->h->addRoles([
            Roles::USER,
            Roles::APP,
            Roles::APP_ADMIN,
            Roles::APP_MANAGER,
            Roles::GROUP_ADMIN,
            Roles::GROUP_MANAGER,
            Roles::USER_ADMIN
        ]);

        $this->group = $this->h->addGroups(['test-g'])[0];

        $this->mid = $this->h->addCharacterMain('Manager', 11, [Roles::USER, Roles::APP_MANAGER, Roles::GROUP_MANAGER])
            ->getPlayer()->getId();

        $this->player = $this->h->addCharacterMain('Admin', 12,
            [Roles::USER, Roles::APP_ADMIN, Roles::USER_ADMIN, Roles::GROUP_ADMIN])->getPlayer();
    }
}
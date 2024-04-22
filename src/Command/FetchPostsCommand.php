<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;



use Symfony\Component\HttpClient\HttpClient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Posts;
use App\Entity\User;
use App\Entity\Geo;
use App\Entity\Company;
use App\Entity\Address;

#[AsCommand(
    name: 'fetchPosts',
    description: 'Fech posts from API(https://jsonplaceholder.typicode.com/)',
)]
class FetchPostsCommand extends Command
{

    protected $output;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $this->output = $output;

        $data = $this->fetchData();

        if($data !== null) {

            // Just in case, validate schema
            $this->checkDataIntegrity($data);
            $this->populateDB($data);
            $io->success('Looks fine!!!');
            return Command::SUCCESS;

        } else {

            $io->failure('Command was aborted due to status code of request');
            return Command::FAILURE; 

        }

    }

    public function fetchData() {
		
        $data = [
            'users' => [
                'name'   => 'Users',
                'status' => null,
                'collection' => null,
                'location'   => 'https://jsonplaceholder.typicode.com/users'
            ],
            'posts' => [
                'name'   => 'Posts',
                'status' => null,
                'collection' => null,
                'location'   => 'https://jsonplaceholder.typicode.com/posts'
            ]
        ];

        foreach($data as &$request) {

            $this->output->writeln("Requests data from: $request[location], as $request[name]");
            $client = HttpClient::create();
            $response = $client->request('GET', $request['location']);
            $request['status'] = $response->getStatusCode(); 

            if($request['status'] !== 200) {
                $this->output->writeln(sprintf("Something went wrong and $request[name] returns \"$request[status]\" status code"));
                $data = null;
                break;
            }

            $request['collection'] = new ArrayCollection( $response->toArray() );
            $request['collectionSize'] = sizeof($request['collection']);

            $this->output->writeln("<info>Downloaded $request[collectionSize] records from $request[name]</info>");
             
        }

        return $data;

    }

    public function checkDataIntegrity(array &$data):bool {

        $this->output->writeln('Just in case, validate schema...');

        $schema = new class($this->output)  {

            private $postsSchema;
            private $usersSchema;

            public function __construct($output) {

                $this->output = $output;
                $this->makeSchemaTemplates();

            }

            private function makeSchemaTemplates():bool {

                $this->postsSchema = [

                    'id' => 'integer',
                    'userId' => 'integer',
                    'title' => 'string',
                    'body' => 'string',

                ];

                $this->usersSchema = [

                    'id' => 'integer',
                    'name' => 'string',
                    'username' => 'string',
                    'email' => 'string',
                    'address' => [
                        'street' => 'string',
                        'suite' => 'string',
                        'city' => 'string',
                        'zipcode' => 'string',
                        'geo' => [
                            'lat' => 'string',
                            'lng' => 'string',
                        ]
                    ],
                    'phone' => 'string',
                    'website' => 'string',
                    'company' => [
                        'name' => 'string',
                        'catchPhrase' => 'string',
                        'bs' => 'string',
                    ]

                ];

                return true;

            }

            /**
             * @param {String} tableName 
             * @param {Array} level This is map/route to requested dimm of schema 
             * 
             * @return array dimm
             */
            private function pickTemplate(string $tableName, array $level = [0]):array {

                $schema;

                switch($tableName) {

                    case 'Users': $schema = [&$this->usersSchema]; break;
                    case 'Posts': $schema = [&$this->postsSchema]; break;
                    default: return undefined;

                }

                // Go deeper in dimension( there is no need to return all schema if particular dimm was requested )
                forEach($level as $dimm) { $schema = &$schema[$dimm]; }

                return $schema;

            }

            /**
             * Does match schema?
             * @param{String} tableName Required to pick schema
             * @param{String} tableRow Data object for check
             * @param{String} rowIndex Only for debug
             * 
             * @return bool
             */
            public function match(string $tableName, array $tableRow, int $rowIndex):bool {

                $isValid = true;
                
                $queue = [[

                    'id' => 0,
                    'list' => $tableRow,
                    'level' => [0]

                ]];

                while(!empty($queue) || $isValid !== false) {
                    
                    /**
                     * !empty = false
                     * isset($queue[0]) = false
                     * count($queue) != 0   
                     * but still loop executed witch empty queue so i use: 
                     * if(empty($queue)) break;
                     */

                    if(empty($queue)) break;
                    $item = array_shift($queue);
    
                    foreach($item['list'] as $tableCol => $colVal) {

                        if(gettype($colVal) === 'array') {
                           
                            array_push($queue, [

                                'id' => $item['id'].'->'.$tableCol,
                                'list' => $colVal,
                                'level' => array_merge($item['level'], (array)$tableCol)

                            ]);

                        } else {

                            $template = $this->pickTemplate($tableName, $item['level']);

                            if(!isset($template[$tableCol])) {

                                $this->output->writeln("<comment>$tableName $rowIndex was skipped, because $tableCol property on index $item[id] doesn't match its template</comment>");
                                $isValid = false;
                                break;

                            } elseif(gettype($colVal) !== $template[$tableCol]) {
           
                                $this->output->writeln("<comment>$tableName $rowIndex was skipped, because property $tableCol on index $item[id], have wrong type</comment>");
                                $isValid = false;
                                break;
                
                            } 

                        }

                    }

                };

                return $isValid;

            }

        };


        foreach($data as &$table) {

            $table['collection'] = $table['collection']->filter(function($row) use(&$schema, &$table):bool {

                return $schema->match($table['name'], $row, $table['collection']->indexof($row));

            });

            $size = sizeof($table['collection']);
            $this->output->writeln("$table[name]: $size out of $table[collectionSize] records are valid");

        }

        return true;

    }
    
    public function populateDB(array $data):bool {
        $i = 0;
        foreach($data['users']['collection'] as $userInstance) {

            $address = new Address();
            $company = new Company();
            $geo = new Geo();
            $user = new User();
            
            $user->setName($userInstance['name']);
            $user->setUsername($userInstance['username']);
            $user->setEmail($userInstance['email']);
            // Address
                $address->setStreet($userInstance['address']['street']);
                $address->setSuite($userInstance['address']['suite']);
                $address->setCity($userInstance['address']['city']);
                $address->setZipcode($userInstance['address']['zipcode']);
                //Geo
                    $geo->setLat($userInstance['address']['geo']['lat']);
                    $geo->setLng($userInstance['address']['geo']['lng']);
            $user->setPhone($userInstance['phone']);
            $user->setWebsite($userInstance['website']);
            // company
                $company->setName($userInstance['company']['name']);
                $company->setCatchPhrase($userInstance['company']['catchPhrase']);
                $company->setBs($userInstance['company']['bs']);

            // Relation
            $user->setAddress($address);
            $user->setCompany($company);
            $address->setGeo($geo);


            // Posts
            foreach($data['posts']['collection'] as &$postInstance) {
                // Find proper one
                if($postInstance['userId'] === $userInstance['id']) {
                    $i++;
                    $post = new Posts();
                    $post->setUserId($postInstance['userId']);
                    $post->setTitle($postInstance['title']);
                    $post->setBody($postInstance['body']);
                    $post->setUser($user);
                    $this->em->persist($post);
                }

            }

            $this->em->persist($address);
            $this->em->persist($company);
            $this->em->persist($geo);
            $this->em->persist($user);
           
        }
      
        $this->em->flush();
        $this->output->writeln('Database was updated');
        return true;

    }

}

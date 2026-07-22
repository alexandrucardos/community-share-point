<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Item\ItemEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HouseholdItemsController extends AbstractController
{
    private const array MOCKED_ITEMS = [
        ['id' => '1', 'name' => 'Vacuum Cleaner', 'status' => 'Available', 'description' => 'Cordless stick vacuum, great suction on both carpet and hardwood.', 'color' => '2563eb'],
        ['id' => '2', 'name' => 'Blender', 'status' => 'In Use', 'description' => 'High-speed blender, currently borrowed by apartment 2B, back Friday.', 'color' => 'db2777'],
        ['id' => '3', 'name' => 'Cordless Drill', 'status' => 'Available', 'description' => '18V drill with two charged batteries and a full bit set.', 'color' => 'ea580c'],
        ['id' => '4', 'name' => 'Table Lamp', 'status' => 'Needs Repair', 'description' => 'Reading lamp with a flickering bulb, the switch needs replacing.', 'color' => '65a30d'],
        ['id' => '5', 'name' => 'Office Chair', 'status' => 'Available', 'description' => 'Ergonomic mesh chair with adjustable height and lumbar support.', 'color' => '7c3aed'],
        ['id' => '6', 'name' => 'Toaster', 'status' => 'Reserved', 'description' => '4-slice toaster, reserved for the weekend brunch.', 'color' => '0891b2'],
    ];

    #[Route('/items', name: 'household_items_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('household_items/index.html.twig', [
            'items' => $this->mockedItems(),
        ]);
    }

    /**
     * @return ItemEntity[]
     */
    private function mockedItems(): array
    {
        return array_map(
            function (array $data): ItemEntity {
                $item = new ItemEntity($data['id']);
                $item->setName($data['name']);
                $item->setStatus($data['status']);
                $item->setDescription($data['description']);
                $item->setImageUrl($this->placeholderImage($data['name'], $data['color']));

                return $item;
            },
            self::MOCKED_ITEMS
        );
    }

    private function placeholderImage(string $name, string $color): string
    {
        $initials = strtoupper(substr($name, 0, 1) . (strrpos($name, ' ') !== false ? $name[strrpos($name, ' ') + 1] : ''));

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200">
                <rect width="300" height="200" fill="#{$color}"/>
                <text x="150" y="112" font-family="sans-serif" font-size="64" fill="#ffffff" text-anchor="middle">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

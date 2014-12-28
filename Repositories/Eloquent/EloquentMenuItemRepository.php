<?php namespace Modules\Menu\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Laracasts\Commander\Events\EventGenerator;
use Laracasts\Commander\Events\DispatchableTrait;
use Modules\Menu\Events\MenuItemWasCreated;
use Modules\Menu\Repositories\MenuItemRepository;
use Modules\Core\Repositories\Eloquent\EloquentBaseRepository;

class EloquentMenuItemRepository extends EloquentBaseRepository implements MenuItemRepository
{
    use EventGenerator, DispatchableTrait;

    public function create($data)
    {
        $menuItem = $this->model->create($data);

        $this->raise(new MenuItemWasCreated($menuItem));
        $this->dispatchEventsFor($this);

        return $menuItem;
    }

    public function update($menuItem, $data)
    {
        $menuItem->update($data);

        return $menuItem;
    }

    /**
     * Get online root elements
     *
     * @param int $menuId
     * @return object
     */
    public function rootsForMenu($menuId)
    {
        return $this->model->whereHas('translations', function (Builder $q) {
            $q->where('status', 1);
            $q->where('locale', App::getLocale());
        })->with('translations')->whereMenuId($menuId)->orderBy('position')->get();
    }

    /**
     * Get all root elements
     *
     * @param int $menuId
     * @return object
     */
    public function allRootsForMenu($menuId)
    {
        return $this->model->with('translations')->whereMenuId($menuId)->orderBy('position')->get();
    }

    /**
     * Get Items to build routes
     *
     * @return Array
     */
    public function getForRoutes()
    {
        $menuitems = DB::table('menus')
            ->select(
                'primary',
                'menuitems.id',
                'menuitems.parent_id',
                'menuitems.module_name',
                'menuitem_translations.uri',
                'menuitem_translations.locale'
            )
            ->join('menuitems', 'menus.id', '=', 'menuitems.menu_id')
            ->join('menuitem_translations', 'menuitems.id', '=', 'menuitem_translations.menuitem_id')
            ->where('uri', '!=', '')
            ->where('module_name', '!=', '')
            ->where('status', '=', 1)
            ->where('primary', '=', 1)
            ->orderBy('module_name')
            ->get();

        $menuitemsArray = [];
        foreach ($menuitems as $menuitem) {
            $menuitemsArray[$menuitem->module_name][$menuitem->locale] = $menuitem->uri;
        }

        return $menuitemsArray;
    }

    /**
     * Get the root menu item for the given menu id
     *
     * @param int $menuId
     * @return object
     */
    public function getRootForMenu($menuId)
    {
        return $this->model->with('translations')->where(['menu_id' => $menuId, 'is_root' => true])->firstOrFail();
    }

    /**
     * Return a complete tree for the given menu id
     *
     * @param int $menuId
     * @return object
     */
    public function getTreeForMenu($menuId)
    {
        $items = $this->rootsForMenu($menuId);

        return $items->nest();
    }

    /**
     * @param string $uri
     * @param string $locale
     * @return object
     */
    public function findByUriInLanguage($uri, $locale)
    {
        return $this->model->whereHas('translations', function (Builder $q) use ($locale, $uri) {
            $q->where('status', 1);
            $q->where('locale', $locale);
            $q->where('uri', $uri);
        })->with('translations')->first();
    }
}

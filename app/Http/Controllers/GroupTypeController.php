<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupType\CreateGroupTypeRequest;
use App\Http\Requests\GroupType\SaveOrderAdDisplayRequest;
use App\Http\Requests\GroupType\UpdateGroupTypeRequest;
use App\Models\GroupType;

/**
 * Class GroupTypeController
 * @package App\Http\Controllers
 */
class GroupTypeController extends Controller
{
	/**
	 * @param CreateGroupTypeRequest $request
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 */
	public function create (CreateGroupTypeRequest $request)
	{
		\DB::beginTransaction();

		$fileName = slugIt($request->display_name);

		$lastGroupType = GroupType::orderBy('display_order', 'DESC')->first();
		if (null === $lastGroupType)
			$displayOrder = 1;
		else
			$displayOrder = $lastGroupType->display_order + 1;

		$filePath = 'groups/' . $fileName . '.png';

		$groupType = GroupType::create([
			'slug'          => $fileName,
			'display_name'  => $request->display_name,
			'requirements'  => $request->requirements,
			'duration'      => $request->duration,
			'description'   => $request->description,
			'img'           => $filePath,
			'display_order' => $displayOrder,
		]);

		$imagePath = $groupType->getRealImagePath();

		if (\File::exists($imagePath))
			\File::delete($imagePath);

		\Image::make($request->image)
			  ->encode('png', 50)
			  ->save($imagePath);

		\DB::commit();

		return $this->sendResponse($groupType);
	}

	/**
	 * @param GroupType              $groupType
	 * @param UpdateGroupTypeRequest $request
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 */
	public function update (GroupType $groupType, UpdateGroupTypeRequest $request)
	{
		\DB::beginTransaction();

		$fileName     = slugIt($request->display_name);
		$oldImagePath = $groupType->getRealImagePath();
		$filePath     = 'groups/' . $fileName . '.png';

		if (null !== $request->image) {

			if (\File::exists($oldImagePath))
				\File::delete($oldImagePath);

			$groupType->img = $filePath;
			\Image::make($request->image)
				  ->encode('png', 50)
				  ->save($oldImagePath);
		}

		if ($fileName !== $groupType->slug && null === $request->image) {
			$groupType->img = $filePath;
			$newImagePath   = $groupType->getRealImagePath();
			rename($oldImagePath, $newImagePath);
		}

		$groupType->description  = $request->description;
		$groupType->duration     = $request->duration;
		$groupType->requirements = $request->requirements;
		$groupType->display_name = $request->display_name;
		$groupType->slug         = $fileName;
		$groupType->save();

		\DB::commit();

		$groupType->fresh();

		return $this->sendResponse($groupType);
	}

	/**
	 * @param GroupType $groupType
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 */
	public function delete (GroupType $groupType)
	{
		$groupType->delete();

		if (\File::exists($imagePath = $groupType->getRealImagePath()))
			\File::delete($imagePath);

		return $this->sendResponse();
	}

	/**
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 */
	public function get ()
	{
		$groupTypes = GroupType::orderBy('display_order')
							   ->get();

		return $this->sendResponse($groupTypes);
	}

	/**
	 * @param SaveOrderAdDisplayRequest $request
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
	 */
	public function saveOrderAndDisplay (SaveOrderAdDisplayRequest $request)
	{
		collect($request->group_types)->each(function ($groupType) {
			/** @var GroupType $groupType */
			GroupType::whereSlug($groupType[ 'slug' ])
					 ->update([
						 'display'       => $groupType[ 'display' ],
						 'display_order' => $groupType[ 'display_order' ],
					 ]);
		});

		return $this->sendResponse(GroupType::all());
	}
}

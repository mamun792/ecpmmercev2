<?php 


namespace App\Services\GeneralSetting;

use App\Models\GeneralSetting;

class GeneralSettingService{

  public function updateOrCreateSettings(array $data)
    {
        // Since we want to update or create a single record, we can use id 1
        return GeneralSetting::updateOrCreate(
            ['id' => 1], // Assuming we want one main settings record
            $data
        );
    }

    public function getSettings()
    {
        return GeneralSetting::first() ?? new GeneralSetting();
    }

}
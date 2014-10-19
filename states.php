<?php
function convert_state($name, $to = 'name')
{
    $states = array(
        array('abbrev' => 'AL', 'name' => 'Alabama'),
        array('abbrev' => 'AK', 'name' => 'Alaska'),
        array('abbrev' => 'AS', 'name' => 'American Samoa'),
        array('abbrev' => 'AZ', 'name' => 'Arizona'),
        array('abbrev' => 'AR', 'name' => 'Arkansas'),
        array('abbrev' => 'AF', 'name' => 'Armed Forces Africa'),
        array('abbrev' => 'AA', 'name' => 'Armed Forces Americas'),
        array('abbrev' => 'AC', 'name' => 'Armed Forces Canada'),
        array('abbrev' => 'AE', 'name' => 'Armed Forces Europe'),
        array('abbrev' => 'AM', 'name' => 'Armed Forces Middle East'),
        array('abbrev' => 'AP', 'name' => 'Armed Forces Pacific'),
        array('abbrev' => 'CA', 'name' => 'California'),
        array('abbrev' => 'CO', 'name' => 'Colorado'),
        array('abbrev' => 'CT', 'name' => 'Connecticut'),
        array('abbrev' => 'DE', 'name' => 'Delaware'),
        array('abbrev' => 'DC', 'name' => 'District of Columbia'),
        array('abbrev' => 'FM', 'name' => 'Federated States Of Micronesia'),
        array('abbrev' => 'FL', 'name' => 'Florida'),
        array('abbrev' => 'GA', 'name' => 'Georgia'),
        array('abbrev' => 'GU', 'name' => 'Guam'),
        array('abbrev' => 'HI', 'name' => 'Hawaii'),
        array('abbrev' => 'ID', 'name' => 'Idaho'),
        array('abbrev' => 'IL', 'name' => 'Illinois'),
        array('abbrev' => 'IN', 'name' => 'Indiana'),
        array('abbrev' => 'IA', 'name' => 'Iowa'),
        array('abbrev' => 'KS', 'name' => 'Kansas'),
        array('abbrev' => 'KY', 'name' => 'Kentucky'),
        array('abbrev' => 'LA', 'name' => 'Louisiana'),
        array('abbrev' => 'ME', 'name' => 'Maine'),
        array('abbrev' => 'MH', 'name' => 'Marshall Islands'),
        array('abbrev' => 'MD', 'name' => 'Maryland'),
        array('abbrev' => 'MA', 'name' => 'Massachusetts'),
        array('abbrev' => 'MI', 'name' => 'Michigan'),
        array('abbrev' => 'MN', 'name' => 'Minnesota'),
        array('abbrev' => 'MS', 'name' => 'Mississippi'),
        array('abbrev' => 'MO', 'name' => 'Missouri'),
        array('abbrev' => 'MT', 'name' => 'Montana'),
        array('abbrev' => 'NE', 'name' => 'Nebraska'),
        array('abbrev' => 'NV', 'name' => 'Nevada'),
        array('abbrev' => 'NH', 'name' => 'New Hampshire'),
        array('abbrev' => 'NJ', 'name' => 'New Jersey'),
        array('abbrev' => 'NM', 'name' => 'New Mexico'),
        array('abbrev' => 'NY', 'name' => 'New York'),
        array('abbrev' => 'NC', 'name' => 'North Carolina'),
        array('abbrev' => 'ND', 'name' => 'North Dakota'),
        array('abbrev' => 'MP', 'name' => 'Northern Mariana Islands'),
        array('abbrev' => 'OH', 'name' => 'Ohio'),
        array('abbrev' => 'OK', 'name' => 'Oklahoma'),
        array('abbrev' => 'OR', 'name' => 'Oregon'),
        array('abbrev' => 'PW', 'name' => 'Palau'),
        array('abbrev' => 'PA', 'name' => 'Pennsylvania'),
        array('abbrev' => 'PR', 'name' => 'Puerto Rico'),
        array('abbrev' => 'RI', 'name' => 'Rhode Island'),
        array('abbrev' => 'SC', 'name' => 'South Carolina'),
        array('abbrev' => 'SD', 'name' => 'South Dakota'),
        array('abbrev' => 'TN', 'name' => 'Tennessee'),
        array('abbrev' => 'TX', 'name' => 'Texas'),
        array('abbrev' => 'UT', 'name' => 'Utah'),
        array('abbrev' => 'VT', 'name' => 'Vermont'),
        array('abbrev' => 'VI', 'name' => 'Virgin Islands'),
        array('abbrev' => 'VA', 'name' => 'Virginia'),
        array('abbrev' => 'WA', 'name' => 'Washington'),
        array('abbrev' => 'WV', 'name' => 'West Virginia'),
        array('abbrev' => 'WI', 'name' => 'Wisconsin'),
        array('abbrev' => 'WY', 'name' => 'Wyoming'),
    );

    $return = false;
    foreach ($states as $state) {
        if ($to == 'name') {
            if (strtolower($state['abbrev']) == strtolower($name)) {
                $return = $state['name'];
                break;
            }
        } else if ($to == 'abbrev') {
            if (strtolower($state['name']) == strtolower($name)) {
                $return = strtoupper($state['abbrev']);
                break;
            }
        }
    }
    return $return;
}

?>
<?php
function vayuma_stakeholder_configs(): array {
 return [
  'input_supplier'=>['Input Supplier','va-icon-crops','Supply certified seeds, fertiliser, equipment and farm inputs.',['Products & stock','Farmer requests','Delivery coordination']],
  'manufacturer'=>['Manufacturer','va-icon-tag','Process agricultural produce and supply finished products.',['Products','Processing capacity','Orders']],
  'warehouse_provider'=>['Warehouse Provider','va-icon-warehouse','Provide safe agricultural storage and handling services.',['Storage space','Produce accepted','Storage requests']],
  'retailer'=>['Retailer','va-icon-marketplace','Buy and sell agricultural products.',['Products','Customers','Orders']],
  'agribusiness'=>['Agribusiness','va-icon-crops','Provide agricultural products and commercial services.',['Services','Products','Partnerships']],
  'veterinary_officer'=>['Veterinary / Animal Health Professional','va-icon-veterinary','Provide animal-health services within your professional scope.',['Animal health services','Service coverage','Credentials']],
  'extension_officer'=>['Agricultural Extension Officer','va-icon-crops','Provide farmer training, advisory and field extension support.',['Farmer advisory','Field support','Service coverage']],
  'insurance_provider'=>['Insurance Provider','va-icon-checkmark','Provide agricultural and livestock risk protection.',['Policies','Coverage','Claims support']],
  'government_officer'=>['Government Officer','va-icon-checkmark','Connect farmers with public agricultural programmes and services.',['Programmes','Public notices','Jurisdiction']],
  'ngo_partner'=>['NGO Partner','va-icon-ngo','Support agricultural development programmes, training and support.',['Projects','Training','Grants & support']],
  'agricultural_service_provider'=>['Agricultural Service Provider','va-icon-agricultural-services','Offer machinery, irrigation, spraying and other farm services.',['Service catalogue','Bookings','Service areas']],
 ];
}
function vayuma_role_config(string $role): ?array { $all=vayuma_stakeholder_configs(); return $all[$role]??null; }

(() => {
  const $ = id => document.getElementById(id);
  const status = $('status'), matches = $('matches');
  let requestNumber = 0;
  function clearWeather(message) {
    ['temperature','humidity','rainfall','wind'].forEach(id => $(id).textContent = '—');
    $('condition').textContent = 'Weather unavailable'; $('forecast').replaceChildren();
    $('farmingAdvice').textContent = 'No current forecast is available; do not rely on previous values.';
    $('climateWarnings').replaceChildren(); status.textContent = message;
  }
  const format = (v, unit) => typeof v === 'number' && Number.isFinite(v) ? `${v}${unit}` : 'Unavailable';
  async function getForecast(lat, lon, label) {
    const request = ++requestNumber;
    clearWeather('Loading forecast…');
    try {
      const res = await fetch('get_climate.php?' + new URLSearchParams({mode:'forecast',lat:String(lat),lon:String(lon),remember:$('keepForecast').checked?'1':'0'}), {cache:'no-store'});
      const data = await res.json(); if (request !== requestNumber) return; if (!res.ok || !data.success) throw new Error(data.message || 'Weather unavailable.');
      $('temperature').textContent = format(data.current.temperature, ' °C'); $('humidity').textContent = format(data.current.humidity, '%');
      $('rainfall').textContent = format(data.current.rainfall, ' mm'); $('wind').textContent = format(data.current.wind, ' km/h'); $('condition').textContent = data.current.condition;
      status.textContent = `${label} (${Number(lat).toFixed(3)}, ${Number(lon).toFixed(3)}). ${data.source}. Model time: ${data.forecast_time} ${data.timezone}. Retrieved: ${data.retrieved_at}.`;
      $('farmingAdvice').textContent = 'Check soil moisture and the forecast before irrigating. Consider postponing spraying if rain or strong wind is expected; always follow the product label and local expert advice.';
      data.forecast.forEach(day => { const row=document.createElement('div'); row.className='forecast-day'; row.textContent = `${day.date}: ${day.condition}; ${format(day.min,' °C')} – ${format(day.max,' °C')}; forecast rain ${format(day.rainfall,' mm/day')}; chance ${format(day.rain_probability,'%')}; maximum wind ${format(day.max_wind,' km/h')}.`; $('forecast').append(row); });
      const warnings=[];
      if (data.forecast.some(d=>typeof d.max==='number' && d.max>=32)) warnings.push('Heat indicator: a forecast maximum is at least 32 °C; watch for crop and livestock heat stress.');
      if (data.forecast.some(d=>typeof d.rainfall==='number' && d.rainfall>=25)) warnings.push('Heavy-rain indicator: at least 25 mm is forecast in a day; monitor drainage and official alerts.');
      if (data.forecast.some(d=>typeof d.max_wind==='number' && d.max_wind>=40)) warnings.push('Strong-wind indicator: at least 40 km/h is forecast; check local conditions before spraying.');
      warnings.forEach(text=>{const li=document.createElement('li');li.textContent=text;$('climateWarnings').append(li);});
    } catch (error) { if (request === requestNumber) clearWeather(error.message || 'Weather unavailable.'); }
  }
  $('climateForm').addEventListener('submit', async e => {
    e.preventDefault(); const request = ++requestNumber; $('matchArea').hidden=true; clearWeather('Finding matching places…');
    try { const params=new URLSearchParams({mode:'search',location:$('location').value.trim(),county:$('county').value.trim()});
      const res=await fetch('get_climate.php?'+params,{cache:'no-store'});const data=await res.json();if(request!==requestNumber)return;if(!res.ok||!data.success)throw new Error(data.message||'Location search unavailable.');
      matches.replaceChildren(); if (!data.matches.length) {status.textContent='No matching place found. Try a nearby town or remove the county filter.';return;}
      data.matches.forEach(place=>{const option=document.createElement('option');option.value=JSON.stringify(place);option.textContent=[place.name,place.county,place.country].filter(Boolean).join(', ');matches.append(option);});
      $('matchArea').hidden=false;status.textContent='Choose the correct matching place before loading the forecast.';
    } catch(error) {if(request===requestNumber)clearWeather(error.message || 'Location search unavailable.');}
  });
  $('showForecast').addEventListener('click',()=>{try{const p=JSON.parse(matches.value);getForecast(p.latitude,p.longitude,matches.selectedOptions[0].textContent);}catch(_){clearWeather('Choose a location first.');}});
  $('useGps').addEventListener('click',()=>{if(!navigator.geolocation){clearWeather('GPS is not available on this device.');return;}const request=++requestNumber;status.textContent='Waiting for permission to use your location…';navigator.geolocation.getCurrentPosition(p=>{if(request===requestNumber)getForecast(p.coords.latitude,p.coords.longitude,'Phone GPS location');},()=>{if(request===requestNumber)clearWeather('GPS was not available or permission was denied. Search for a place instead.');},{enableHighAccuracy:false,timeout:12000,maximumAge:60000});});
})();

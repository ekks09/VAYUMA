<?php
/**
 * Render the shared registered-location controls.
 * Include this file from a profile form, then call vayuma_location_fields().
 */
function vayuma_location_fields(array $values = []): void {
    $county = (int)($values['county_id'] ?? 0);
    $subCounty = (int)($values['sub_county_id'] ?? 0);
    $ward = (int)($values['ward_id'] ?? 0);
    $village = htmlspecialchars((string)($values['village_estate'] ?? ''), ENT_QUOTES, 'UTF-8');
    ?>
    <fieldset class="va-location-fields">
      <legend>Registered location</legend>
      <div class="va-location-grid">
        <label>County <span aria-hidden="true">*</span>
          <select name="county_id" id="vayuma-county" required>
            <option value="">Select county</option>
          </select>
        </label>
        <label>Sub-county <span aria-hidden="true">*</span>
          <select name="sub_county_id" id="vayuma-subcounty" required disabled>
            <option value="">Select county first</option>
          </select>
        </label>
        <label>Ward <span aria-hidden="true">*</span>
          <select name="ward_id" id="vayuma-ward" required disabled>
            <option value="">Select sub-county first</option>
          </select>
        </label>
        <label>Village / Estate (optional)
          <input type="text" name="village_estate" maxlength="150" value="<?= $village ?>" placeholder="Village, estate or local area">
        </label>
      </div>
      <p class="va-location-note">Your registered location is separate from any service area or official work jurisdiction.</p>
    </fieldset>
    
<style>
.va-language-selector {
  display: flex; align-items: center; justify-content: flex-end; gap: var(--va-space-3);
  flex-wrap: wrap; padding: var(--va-space-3) var(--va-space-4);
  font-size: var(--va-font-size-sm); position: relative; z-index: 1000;
}
.va-language-selector span { font-weight: var(--va-font-weight-semibold); }
.va-language-selector a {
  display: inline-block; padding: var(--va-space-2) var(--va-space-3);
  border: 1px solid var(--va-border); border-radius: var(--va-radius-full);
  text-decoration: none; color: var(--va-text-secondary); font-weight: var(--va-font-weight-semibold);
  background: var(--va-surface);
}
.va-language-selector a.active {
  background: var(--va-primary); color: #fff; border-color: var(--va-primary);
}
.va-language-selector a:focus-visible { outline: 3px solid var(--va-primary); outline-offset: 2px; }
@media (max-width: 600px) { .va-language-selector { justify-content: center; } }

.va-location-fields {
  border: 1px solid var(--va-border); border-radius: var(--va-radius-md);
  padding: var(--va-space-4); margin: var(--va-space-5) 0;
}
.va-location-fields legend {
  font-weight: var(--va-font-weight-bold); padding: 0 var(--va-space-2);
  color: var(--va-primary);
}
.va-location-grid {
  display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--va-space-4);
}
.va-location-grid label {
  display: flex; flex-direction: column; gap: var(--va-space-1);
  font-weight: var(--va-font-weight-semibold);
}
.va-location-grid input, .va-location-grid select {
  width: 100%; padding: var(--va-space-3); border: 1px solid var(--va-border);
  border-radius: var(--va-radius); font: inherit; background: #fff;
}
.va-location-note {
  font-size: var(--va-font-size-xs); color: var(--va-text-muted);
  margin: var(--va-space-3) 0 0;
}
@media (max-width: 650px) { .va-location-grid { grid-template-columns: 1fr; } }
</style>
    <script>
    (() => {
      const county = document.getElementById('vayuma-county');
      const sub = document.getElementById('vayuma-subcounty');
      const ward = document.getElementById('vayuma-ward');
      const selected = {county: <?= $county ?>, sub: <?= $subCounty ?>, ward: <?= $ward ?>};
      const endpoint = 'api/location_options.php';
      async function load(select, type, parentId, placeholder, selectedId=0) {
        select.innerHTML = '';
        const first = document.createElement('option');
        first.value = ''; first.textContent = placeholder; select.appendChild(first);
        select.disabled = type !== 'county' && !parentId;
        if (type !== 'county' && !parentId) return;
        const url = type === 'county' ? `${endpoint}?type=county` : `${endpoint}?type=${type}&parent_id=${encodeURIComponent(parentId)}`;
        try {
          const response = await fetch(url, {headers:{'Accept':'application/json'}});
          const data = await response.json();
          if (!response.ok || !data.success) throw new Error(data.message || 'Location loading failed');
          data.items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id; option.textContent = item.name;
            if (Number(item.id) === Number(selectedId)) option.selected = true;
            select.appendChild(option);
          });
          select.disabled = false;
        } catch (error) {
          first.textContent = 'Could not load locations — refresh and try again';
          select.disabled = true;
          console.error(error);
        }
      }
      county.addEventListener('change', async () => {
        await load(sub,'sub_county',county.value,'Select sub-county');
        await load(ward,'ward',0,'Select sub-county first');
      });
      sub.addEventListener('change', () => load(ward,'ward',sub.value,'Select ward'));
      (async () => {
        await load(county,'county',0,'Select county',selected.county);
        if (selected.county) {
          await load(sub,'sub_county',selected.county,'Select sub-county',selected.sub);
          if (selected.sub) await load(ward,'ward',selected.sub,'Select ward',selected.ward);
        }
      })();
    })();
    </script>
    <?php
}

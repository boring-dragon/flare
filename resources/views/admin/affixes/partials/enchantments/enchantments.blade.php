<div class="container-fluid">
  <x-tabs.pill-tabs-container>
      <x-tabs.tab
        tab="effects-stats"
        selected="true"
        active="true"
        title="Effects Stats"
      />
      <x-tabs.tab
        tab="effects-skills"
        selected="false"
        active="false"
        title="Effects Skills"
      />
      <x-tabs.tab
        tab="bonuses"
        selected="false"
        active="false"
        title="Bonus (increasing) Enchantments"
      />
      <x-tabs.tab
        tab="reduces-enemy-stats"
        selected="false"
        active="false"
        title="Reduces Enemy Stats"
      />
      <x-tabs.tab
        tab="reduces-enemy-resistances"
        selected="false"
        active="false"
        title="Reduces Enemy Resistances"
      />
      <x-tabs.tab
        tab="life-stealing-amount"
        selected="false"
        active="false"
        title="Life Stealing"
      />
      <x-tabs.tab
        tab="damage-dealing"
        selected="false"
        active="false"
        title="Damage Dealing"
      />
      <x-tabs.tab
        tab="entrancing"
        selected="false"
        active="false"
        title="Entrancing Enchantments"
      />
      <x-tabs.tab
        tab="devouring-light"
        selected="false"
        active="false"
        title="Devouring Light"
      />
  </x-tabs.pill-tabs-container>
  <x-tabs.tab-content>
    <x-tabs.tab-content-section
      tab="effects-stats"
      active="true"
    >
      <x-cards.card>
        @include('admin.affixes.partials.enchantments.partials.stats-increase')
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="effects-skills"
      active="false"
    >
      <x-cards.card>
        @include('admin.affixes.partials.enchantments.partials.effects-skills')
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="bonuses"
      active="false"
    >
      <x-cards.card>
        @include('admin.affixes.partials.enchantments.partials.bonuses')
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-enemy-stats"
      active="false"
    >
      <x-cards.card>
        @include('admin.affixes.partials.enchantments.partials.stats-decrease')
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-enemy-resistances"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          <p>These affixes do not stack and only effect the enemy. These reduce the following resistances that all enemies have:</p>
          <ul>
            <li>Spell Evasion</li>
            <li>Artifact Annulment</li>
            <li>Affix Resistance</li>
          </ul>
          <p>Should you have many equipped, we will take the best one of them all.</p>
          <p>Much like skill reduction and stat reduction these are applied only if you are not voided and before the fight begins.</p>
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'resistance_reduction',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="life-stealing-amount"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These affixes can steal life from an enemy. If you are a vampire, you want as many of these on all your gear
          as possible. As these will stack, stealing a maximum of 99% of the enemies health.
          If you are not a vampire, having multiple will not do anything for you as we take the best one when determine life to steal
          which only happens during your healing phase (after the enemies attack if you or the enemy are still alive).
          For vampires, these fire as part of their attack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'modifiers',
            'type' => 'steal_life_amount',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="damage-dealing"
      active="false"
    >
      <x-cards.card>

        @include('admin.affixes.partials.enchantments.partials.damage-dealing')
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="entrancing"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These affixes do not stack, how ever if they have stats, skills or other modifiers they will stack unless other wise stated.
          For the entrancing aspect, we don't stack that %. We take the best one. This gives you a chance to stun the enemy allowing
          you to by pass their ability to block or dodge. Enemies can still evade spells and resist them. These make is it so the enemy cannot
          block or dodge.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'entrancing',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="devouring-light"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These affixes use a feature called Devouring Light (apart of the Voidance and Devoidance Family) Devouring light gives you
          a small chance to void the enemy. When you void an enemy, their enchantments and spells will not fire. An enemy still has a chance to void you.
          If you also have a quest item, such as Dead Kings Crown, you will also have whats called Devouring Darkness. No enemy has this, how ever it will allow you to devoid
          and enemy and thus they cannot void you, a devoid cannot cancel another devoid. A void cannot cancel another void.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'devouring_light',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
  </x-tabs.tab-content>
</div>

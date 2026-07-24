# JobHunter Design System

Signal Desk is the shared visual and interaction system for JobHunter. It turns the workflow `Descubrir -> Evaluar -> Seleccionar -> Postular -> Hacer seguimiento` into a precise operating environment where match quality and next actions are always legible.

## Principles

1. **Signal before chrome.** Scores, status, risk, and next action receive the strongest hierarchy.
2. **Density follows intent.** Marketplace is compact and energetic; Tracking is measured; Profile and settings are quiet.
3. **Color carries meaning.** Cobalt means command or active context. Score and state colors never communicate alone.
4. **Review before mutation.** AI-assisted CV changes expose a diff and require explicit confirmation into a new variant.
5. **Every state is designed.** Loading, empty, populated, error, disabled, focus, and success states are mandatory.

## Color

All implementation tokens live in `resources/css/app.css`. Components consume semantic utilities such as `bg-surface`, `text-ink`, `border-line`, and `text-primary`; product UI must not introduce raw color values.

| Role           | Light     | Dark      | Usage                                |
| -------------- | --------- | --------- | ------------------------------------ |
| Canvas         | `#F2F5FB` | `#08101F` | Page background                      |
| Surface        | `#FFFFFF` | `#0E182B` | Primary content                      |
| Raised surface | `#FFFFFF` | `#15213A` | Menus, overlays, raised cards        |
| Subtle surface | `#E9EEF7` | `#1B2944` | Grouping, selected rows, skeletons   |
| Ink            | `#131C31` | `#F4F7FC` | Primary text                         |
| Muted ink      | `#526078` | `#B6C0D1` | Secondary text                       |
| Border         | `#CED6E4` | `#2B3A57` | Dividers and controls                |
| Primary        | `#1749E9` | `#7898FF` | Primary actions, active state, focus |
| Secondary      | `#44546F` | `#B6C4D8` | Supporting actions and context       |

### Match Score

| Range    | Label     | Light foreground / surface | Dark foreground / surface |
| -------- | --------- | -------------------------- | ------------------------- |
| `85-100` | Excelente | `#087A55` / `#DCF8EC`      | `#62DDB2` / `#123D33`     |
| `75-84`  | Muy bueno | `#1764D8` / `#E0ECFF`      | `#8DB5FF` / `#192F5A`     |
| `60-74`  | Aceptable | `#9A5800` / `#FFF0CF`      | `#FFC768` / `#493214`     |
| `0-59`   | Bajo      | `#B42318` / `#FEE4E2`      | `#FF9B94` / `#4A2325`     |

Use `MatchScore.vue` for every visible score. Always render the value and tier label; `Sin analizar` is the null state. The component's signal dial is used at card and detail scale, while its compact instrument is used in dense rows.
Scores round to the nearest integer before assigning a tier, so the visible value and label can never disagree.

### Product States

Success uses the excellent green, warning uses acceptable amber, error uses low red, and info uses very-good blue. Each state pairs color with an icon, title, or explicit text.

## Typography

- **Interface:** Instrument Sans, weights 400, 500, and 600. It is loaded through the Laravel Vite font pipeline.
- **Data:** system monospace with tabular numerals for scores, metrics, dates, and identifiers only.
- **Page title:** 2rem / 2.35rem, 600.
- **Section title:** 1.25rem / 1.6rem, 600.
- **Body:** 1rem / 1.55rem, 400. Reading copy is capped at 70 characters.
- **Compact body:** 0.875rem / 1.3rem, 400 or 500.
- **Metadata:** 0.75rem / 1rem, 600. Uppercase is reserved for short operational labels, never paragraphs.

## Spacing, Radius, and Layout

Spacing follows a 4px base: `4, 8, 12, 16, 24, 32, 48, 64`. Use `gap` for sibling rhythm. Tight control groups use 8-12px; content groups use 16-24px; major sections use 32-64px.

- Control radius: `10px` (`rounded-control`)
- Card radius: `14px` (`rounded-card`)
- Major panel radius: `18px` (`rounded-panel`)
- Minimum interactive target: `44x44px` at every viewport size.
- Reading width: `65-75ch`; operational views may use the full available width.

## Elevation

- `shadow-card`: bordered content resting on the canvas.
- `shadow-raised`: menus, drawers, and protected-focus overlays.
- `shadow-action`: primary controls only.

Every shadow combines a small downward offset with a wider soft falloff. Do not use colored halos or shadow as the only boundary.

## Components

Base components live in `resources/js/components/ui` and use Vue 3 `<script setup>` with typed props.

| Component      | Contract                                                                                                          |
| -------------- | ----------------------------------------------------------------------------------------------------------------- |
| `BaseButton`   | Button or navigation component with four variants, four sizes, disabled and loading states                       |
| `BaseInput`    | Visible label, hint/error association, optional prefix/suffix slots, invalid state                                |
| `BaseSelect`   | Visible label, hint/error association, typed options or custom option slot                                        |
| `BaseTextarea` | Multiline input with the same label, hint, disabled, and error contract as other fields                           |
| `BaseTag`      | Neutral, primary, state, and score tones; pills only for compact metadata                                         |
| `BaseCard`     | Default, raised, subtle, and interactive surfaces without nested-card styling                                     |
| `BaseModal`    | Brief confirmations and destructive decisions with trapped and restored focus                                     |
| `BaseDrawer`   | Contextual workflows in a right-side panel with trapped and restored focus                                        |
| `BaseToast`    | Success, error, and info feedback with a labeled dismiss action                                                   |
| `BaseSkeleton` | Text, circle, and block placeholders; motion reduces automatically                                                |
| `BaseTooltip`  | Supplemental help on hover/focus with top, right, or bottom placement                                              |
| `BaseAvatar`   | User identity with resilient initials fallback and three fixed sizes                                              |
| `CompanyLogo`  | Real logo with resilient initials fallback                                                                        |
| `EmptyState`   | Explains the module, the absence, and the next available action                                                   |
| `MatchScore`   | Consistent score instrument in compact, card, and hero scales                                                     |
| `BaseTabs`     | Roving-focus tabs with arrow, Home, and End keyboard navigation                                                   |
| `BaseDropdown` | Compact action menu with keyboard navigation, Escape dismissal, and focus restoration                             |

## Controls and Content Rules

- Primary buttons use imperative labels such as `Analizar pendientes`, not vague labels such as `Continuar`.
- Inputs always retain a visible label. Placeholder text is an example, not a replacement label.
- Icon-only controls require an `aria-label` and `BaseTooltip` when the action is not universally obvious.
- Drawers support contextual configuration. Modals are limited to confirmations, destructive actions, and protected review.
- Cards group one meaningful object. Do not nest cards to create spacing.
- Empty states name what belongs here and offer the next valid action. Errors identify both the problem and recovery.

## Motion

| Purpose                           | Duration  | Curve                           |
| --------------------------------- | --------- | ------------------------------- |
| Hover and control feedback        | 120-160ms | standard ease-out               |
| Panel and route transition        | 220-320ms | `cubic-bezier(0.16, 1, 0.3, 1)` |
| Score reveal and state settlement | 500-700ms | `cubic-bezier(0.16, 1, 0.3, 1)` |

Animate state, position, clipping, or elevation only when it explains a change. Skeletons use restrained opacity modulation. `prefers-reduced-motion: reduce` collapses all animation and transition durations globally.

## Dark Mode

Dark mode is activated by applying `.dark` to the root element. Components use semantic tokens, so they do not require duplicated `dark:` color classes. Store the user's explicit preference and fall back to the operating-system preference when no choice exists.

## Showcase

The authenticated route `/profile/design-system` is the visual contract for this system. It demonstrates every public primitive in light and dark mode, including loading, empty, populated, error, disabled, focusable, and long-content states. Update the showcase whenever a public component or token changes.

## Accessibility Checklist

- WCAG AA contrast for body text and controls.
- One visible `h1` per page and sequential heading levels.
- Visible `:focus-visible` ring with at least 2px separation.
- Keyboard access for navigation, overlays, Kanban alternatives, and every action.
- Text or icon accompaniment for every semantic color.
- `aria-live` feedback for async saves, analyses, and pipeline progress.
- Loading state preserves layout and exposes a textual status to assistive technology.
- Touch targets are at least 44px on mobile.

## Copy Guardrails

Use professional, direct Spanish. JobHunter _analiza_, _recomienda_, _organiza_ and _ahorra tiempo_. It never guarantees employment outcomes. ATS messaging must state that optimization can improve legibility and keyword alignment but cannot guarantee passage through any ATS filter.

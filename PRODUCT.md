# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Job seekers who need to discover, evaluate, prioritize, and manage job applications. The current local implementation is being tested by its creator; the intended product will support multiple users.

## Product Purpose

JobHunter consolidates job discovery, AI-assisted fit analysis, CV management, and application tracking into one workflow. It helps users identify worthwhile opportunities and maintain an accurate record of each application from discovery through outcome.

## Positioning

The product connects configurable job sources, profile-aware AI analysis, non-destructive CV variants, and application tracking in one workflow. It is currently run locally and is intended to later support deployment with API-key-based integrations.

## Operating Context

Users configure job sources and an AI provider, import and maintain their CV, review marketplace offers, shortlist promising vacancies, and track application status, next actions, and notes. Analysis can use a local Claude CLI session or configured Gemini and OpenRouter API keys. Notion is an optional backup destination for qualifying offers.

## Capabilities and Constraints

- The current application is local-only and uses a single-user login flow.
- The intended product will support multiple users and a deployed environment.
- Job sources include JSearch/RapidAPI, LaraJobs RSS, and optional InfoJobs.
- CV parsing is deterministic; AI-assisted CV review, tailoring, and ATS optimization require explicit user action and save results as separate variants.
- LinkedIn and Indeed are not scraped.
- API keys and external integrations will be used in the future deployed version.

## Brand Commitments

The product name is JobHunter. Product language and existing interface copy are primarily Spanish.

## Evidence on Hand

- Local source repository and `README.md` document the product workflows and setup.
- `storage/app/perfil.md` contains a starter profile, not evidence that future interfaces may fabricate user credentials or work history.
- No testimonials, customer claims, benchmarks, pricing, or deployment claims are available.

## Product Principles

- Keep job-search decisions grounded in the user's actual CV and the original vacancy data.
- Make AI assistance reviewable, opt-in, and non-destructive.
- Bring discovery, prioritization, tailoring, and follow-up into one continuous workflow.
- Preserve user privacy in the current local deployment and make external integrations explicit.
- Support a path from local experimentation to a multi-user deployed product without misrepresenting the current state.

## Accessibility & Inclusion

No product-specific accessibility standard has been confirmed. Future interface work should support job seekers with varied access needs.

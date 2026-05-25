# FastMovieAI - AI Agent Coordination Guide

**Project**: Open-source, commercializable AI-driven short-play/short-video creation platform

## Role Division

Per global CLAUDE.md:

- **Claude**: Architecture design, project planning, plugin system decisions, data/naming conventions, integration patterns
- **Codex**: Implementation, debugging, deployment, testing, environment setup, build fixes

---

## Project Architecture

### Backend (PHP/Webman)

**Tech Stack**: PHP 8.1+, Webman v2.1+ (async framework based on Workerman), MySQL 8.0+, Redis, ThinkORM v2.1+, FFmpeg

**Core Ports**:
- `36300` / `36999`: API server (configurable via `SERVER_PORT` in `.env`)
- `36302`: WebSocket message push
- `37000`: WebSocket PUSH API
- `37001`: WebSocket WSS (secure)

**Plugin Architecture** - Modular system in `fastmovie-admin/plugin/`:
- `user` - Authentication, authorization, user management
- `finance` - Payment processing (Alipay, WeChat Pay), credits/VIP
- `marketing` - Promotion, campaigns, analytics
- `article` - Content management, draft system
- `shortplay` - Core short-play creation engine, video generation
- `model` - AI model registry, provider config
- `notification` - Push notifications, in-app messages
- `control` - Admin settings, feature flags, platform controls

**Key Directories**:
- `app/controller/` - Request handlers (e.g., `IndexController.php` shows SSE streaming pattern)
- `app/exception/Handler.php` - JSON error responses (all errors follow `{success: false, code, msg, data: null}`)
- `app/command/` - CLI commands via Webman
- `config/` - Framework config (database pool, Redis, routes, middleware)
- `plugin/*/` - Plugin code (routes, models, controllers per plugin)

**Entry Point**: `start.php` → `app::run()` → Webman bootstrap

**Database Pool** - Critical config in `.env`:
```
DATABASE_MAX_CONNECTIONS=10
DATABASE_MIN_CONNECTIONS=1
DATABASE_WAIT_TIMEOUT=3
DATABASE_IDLE_TIMEOUT=60
DATABASE_HEARTBEAT_INTERVAL=50
```

**Typical Flow**: Request → Router → Middleware (auth, CORS) → Controller → Plugin Logic → ORM/Cache → Response

---

### Frontend (Vue 3/TypeScript)

**Tech Stack**: Vue 3.5+, TypeScript 5.9+, Vite 7.1+, Element Plus 2.11+, Pinia 3.0+, Vue Router 4.5+

**Dev Port**: `36310` (configured in `.env.development` as `VITE_API_URL`)

**Directory Structure**:
- `src/stores/modules/` - Pinia stores by feature (user, video, character, script, etc.)
- `src/composables/` - Custom Composition API hooks (e.g., `useStorage` with persistence)
- `src/pages/` - Page components by route
- `src/components/` - Reusable UI components
- `src/router/` - Route definitions with meta-driven access control
- `src/api/` - HTTP clients, API wrappers (centralized backend calls)
- `src/i18n/` - Multi-language support (zh-CN, en-US)

**Build Output**: `npm run build` → Dist files deployed to `fastmovie-admin/public/assets/`

**Key Patterns**:
- Composition API with reactive state (no Options API)
- Pinia stores for cross-component state
- Custom composables for reuse (storage, fetch, validation)
- Route meta for role-based access control
- Centralized API error handling

---

## Development Workflows

### Backend Development

**Setup**:
```bash
cd fastmovie-admin
composer install  # Optional - framework handles auto-loading
cp .env.example .env
# Edit .env: DATABASE_*, REDIS_*, PUSH_KEY, PUSH_SECRET
php start.php start  # Dev mode (foreground)
# OR: php start.php start -d  # Daemon mode (production)
```

**Common Commands**:
```bash
rtk php webman      # List all available commands
php start.php stop  # Stop service
php start.php restart
php start.php status
```

**After Install**:
1. Delete `public/install` directory (security)
2. Configure Nginx pseudo-static rules (copy `nginx.example`)
3. Set up PUSH_KEY in Nginx location block
4. Change default admin password (admin / 123456)

### Frontend Development

**Setup**:
```bash
cd fastmovie-vue
npm install
# .env.development auto-creates with VITE_API_URL=http://localhost:36999
npm run dev         # Hot-reload dev server on :36310
```

**Common Commands**:
```bash
npm run build       # Production build
npm run preview     # Test production build locally
vue-tsc --noEmit   # Type check only
```

### Plugin Development

**Create Plugin**:
1. New directory in `fastmovie-admin/plugin/{plugin_name}/`
2. Structure:
   - `plugin.php` - Plugin metadata & hooks
   - `app/controller/` - API endpoints
   - `app/model/` - ORM models
   - `config/` - Routes, event listeners
   - `middleware/` - Custom middleware
3. Register in plugin config
4. Implement `Plugin` interface (init, enable, disable hooks)

**Plugin Hooks** (emit points):
- `plugin.init` - On plugin load
- `plugin.enable` - User enables plugin
- `plugin.disable` - User disables plugin
- Domain-specific: `shortplay.generate`, `finance.payment.success`, etc.

**Data Flow**: Plugin Route → Controller → Model (ThinkORM) → Cache (Redis) → Response

---

## Key Implementation Files (Patterns)

| File | Pattern | Purpose |
|------|---------|---------|
| `fastmovie-admin/app/controller/IndexController.php` | Server-Sent Events (SSE) streaming | Real-time video generation progress |
| `fastmovie-admin/app/exception/Handler.php` | Exception → JSON response | Consistent API error format |
| `fastmovie-admin/config/middleware.php` | Global middleware stack | Auth, CORS, request/response hooks |
| `fastmovie-admin/plugin/finance/app/model/Order.php` | ThinkORM with casts | Type-safe model queries |
| `fastmovie-vue/src/stores/modules/user.ts` | Pinia + storage composable | Persistent auth state |
| `fastmovie-vue/src/composables/useStorage.ts` | LocalStorage wrapper | Reactive persistence |
| `fastmovie-vue/vite.config.ts` | Vendor chunking, gzip, auto-import | Optimized build & DX |

---

## API Response Format

All endpoints return:
```json
{
  "success": true/false,
  "code": "ERROR_CODE" or "SUCCESS_CODE",
  "msg": "Human-readable message (Chinese or English)",
  "data": { /* payload */ } or null
}
```

**Status Codes** (see plugin/*/app/enum/CodeEnum.php):
- `200_*`: Success responses
- `400_*`: Client errors (invalid input, missing params)
- `401_*`: Auth errors (not logged in, expired)
- `403_*`: Permission errors (insufficient role)
- `500_*`: Server errors

---

## Critical Setup Requirements

1. **Installation**: Access `/install` wizard → creates `.env`, database schema, admin account
2. **Environment**: `.env` must include database pool params (non-negotiable for Workerman async)
3. **WebSocket**: PUSH_KEY & PUSH_SECRET are 32-char random strings (auto-generated by installer)
4. **Nginx**: Must proxy `/app/{PUSH_KEY}` to WebSocket port (37001 by default)
5. **Security**: Delete `/install` after setup; don't expose `.env`

---

## Common Pitfalls

- **Database Pool Misconfigured**: Video generation stalls or "connection unavailable" errors → check `DATABASE_MAX_CONNECTIONS`, `DATABASE_HEARTBEAT_INTERVAL`
- **WebSocket Not Working**: Real-time progress not sent → verify PUSH_KEY in Nginx, `.env`, and browser console
- **Frontend API Calls Fail**: CORS or wrong VITE_API_URL → check `.env.development`, browser Network tab
- **Plugin Not Loading**: Verify plugin dir exists, `plugin.php` has correct namespace, registered in config
- **Build Size**: Too large → check Vite chunks config, unused dependencies in `node_modules`

---

## Multi-Language Support

- Backend: Error messages & responses can be en-US or zh-CN (set in request header or user preferences)
- Frontend: `src/i18n/` handles UI localization (selectable in settings)

---

## Continuous Collaboration Pattern

1. **Claude** → Designs plugin architecture, API contract, database schema, naming conventions
2. **Codex** → Implements controllers, models, API integration, UI components
3. **Claude** → Reviews design against OWASP, data integrity, scalability
4. **Codex** → Runs tests, deploys, monitors logs for runtime issues

---

## Links

- Backend repo (Gitee): https://gitee.com/yc_open/ai-short-play
- Frontend repo (Gitee): https://gitee.com/yc_open/ai-short-play-vue
- Demo: https://fastmovie.ai
- Issues: Per repo on Gitee
